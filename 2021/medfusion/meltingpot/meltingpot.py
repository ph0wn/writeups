#!/usr/bin/python3
# Inspired from https://gist.github.com/scturtle/1035886#file-ftpserver-py

import socket
import configparser
import re
import json
import traceback
import os
import time
import hashlib
import datetime
import uuid
from threading import Thread, Lock

CONFIG_FILE='./meltingpot.cfg'
DEBUG = True

class FtpServerThread(Thread):

    def __init__(self, conn, addr, session, meltingpot):
        self.conn = conn
        self.addr = addr
        self.meltingpot = meltingpot
        self.is_logged = False
        self.pasv_mode = False
        self.binary = False
        self.username = ''
        self.password = ''
        self.ftp_verb = ''
        self.servsock = None # FTP server listens on that socket in passive mode
        self.datasock = None # client socket connected to servsock
        self.passive_port = None # e.g 30001
        self.session = session # Session UUID
        Thread.__init__(self)

    def log(self, message):
        log = {}
        log['src_ip'] = self.addr[0]
        log['src_port'] = self.addr[1]
        log['message'] = message
        log['session'] = self.session
        log['ftp_verb'] = self.ftp_verb
        
        if self.username != '':
            log['username']= self.username
        if self.password != '':
            log['password'] = self.password
        
        now = datetime.datetime.now()
        log['timestamp'] = now.strftime('%Y-%m-%dT%H:%M:%S') + ('.%06d' % (now.microsecond / 10000))+ 'Z'

        f = open(self.meltingpot.logfile, "a+")
        f.write(json.dumps(log))
        f.write('\n')
        f.close()
        if DEBUG:
            print("[debug] log() file={0}: json={1}".format(self.meltingpot.logfile, json.dumps(log)))

    def run(self):
        if DEBUG:
            print("[debug] FtpServerThread.run() for {0}:{1}".format(self.addr[0], self.addr[1]))

        while True:
            data = self.conn.recv(1024).decode()
            if not data:
                break

            try:
                self.ftp_verb = data[:4].strip().upper()
                self.log(data)
                func=getattr(self,self.ftp_verb)
                active = func(data)
                if not active:
                    break
            except Exception as e:
                print("Unknown command {0}: gracefully closing connection: data={1}".format(e,data))
                if DEBUG:
                    traceback.print_exc()
                self.conn.send(b'500 Sorry.\r\n')
                break

        self.log("closing session")

        if self.passive_port is not None:
            self.release_passive_port()
        if self.servsock is not None:
            self.servsock.close()
        if self.datasock is not None:
            self.datasock.close()
        self.conn.close()



    def USER(self, data):
        # sanitize username and keep only alphanumeric
        pattern = re.compile('[\W_]+', re.UNICODE)
        self.username = pattern.sub('', data[5:])
        self.conn.sendall(b'331 Looking up password\r\n')
        return True

    def PASS(self, data):
        # sanitize password and keep only some characters
        pattern = re.compile('[\W_#!@]+', re.UNICODE)
        self.password = pattern.sub('', data[5:])
        message = "login attempt {0}/{1}".format(self.username,self.password)
        self.is_logged = False
        try:
            if self.meltingpot.users[self.username] == self.password or self.username == 'anonymous':
                message += ' success'
                self.conn.sendall(b'230 Login successful\r\n')
                self.is_logged = True
        except KeyError as e:
            if DEBUG:
                print("[debug] PASS(): unknown user: {0}".format(self.username))

        if not self.is_logged:
            message += ' failed'
            self.conn.sendall(b'221 Goodbye!\n')

        self.log(message)
        return self.is_logged
            
    def SYST(self, data):
        self.conn.sendall(bytes(self.meltingpot.system+'\r\n', 'utf-8'))
        return True

    def OPTS(self,data):
        self.conn.send(b'200 OK.\r\n')
        return True

    def QUIT(self, data):
        self.conn.send(b'221 Goodbye.\r\n')
        return False

    def NOOP(self, data):
        self.conn.send(b'200 OK.\r\n')
        return True

    def TYPE(self,data):
        try:
            mode=data[5]
            # A and A N = turn binary flag off
            # I and L 8 = turn binary flag on
            if mode =='I' or mode == 'L':
                self.conn.send(b'200 Binary mode.\r\n')
                self.binary=True
            else:
                self.conn.send(b'200 ASCII mode.\r\n')
                self.binary = False
                
            if DEBUG:
                print("[debug] TYPE() Setting mode={0} binary={1}".format(mode,self.binary))

            return True
        
        except IndexError as e:
            if DEBUG:
                print("[debug] TYPE() IndexError: data={0} exc={1}".format(data,e))
        return False
                
    def passive_mode(self, data):        
        # in passive mode, server decides the data port, and client is meant to connect
        self.pasv_mode = True
        self.servsock = socket.socket(socket.AF_INET,socket.SOCK_STREAM)
        self.servsock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        
        # selecting passive port and exit if none left
        self.select_passive_port()
        if self.passive_port is None:
            self.conn.send(b'500 Sorry\r\n')
            return None

        # listen on passive port
        self.servsock.bind((self.meltingpot.host,self.passive_port))
        self.servsock.listen(1)
        ip, port = self.servsock.getsockname()
        assert port == self.passive_port, "[ERROR] PASV: serversock is on port={0} while we expected port={1}".format(port, self.passive_port) # this should never occur

        if DEBUG:
            print("[debug] PASV: listening on {0}:{1}".format(ip, port))
        return port

    def PASV(self, data):
        port = self.passive_mode(data)
        if port is None:
            return False

        # notify client which passive port we use
        if DEBUG:
            print("[debug] PASV: sending back: '227 Entering Passive Mode (%s,%u,%u).\r\n'" %(','.join(self.meltingpot.public_ip.split('.')), port>>8&0xFF, port&0xFF))
        self.conn.send(bytes('227 Entering Passive Mode (%s,%u,%u).\r\n' %(','.join(self.meltingpot.public_ip.split('.')), port>>8&0xFF, port&0xFF), 'utf-8'))
        return True

    def EPSV(self, data):
        port = self.passive_mode(data)
        if port is None:
            return False

        # notify client which passive port we use
        self.conn.send(bytes('229 Extended Passive Mode Entered (|||%u)\r\n' %(port), 'utf-8'))
        return True

    def select_passive_port(self):
        self.meltingpot.lock.acquire()
        for i in range(0, self.meltingpot.nb_passive_ports):
            if DEBUG:
                print("[debug] passive_ports[{0}]={1}".format(i, self.meltingpot.passive_ports[i]))
            if self.meltingpot.passive_ports[i] == False:
                self.meltingpot.passive_ports[i] = True
                self.meltingpot.lock.release()
                self.passive_port = self.meltingpot.first_passive_port + i
                if DEBUG:
                    print("[debug] Selecting passive port: ", self.passive_port)
                return self.passive_port
        self.meltingpot.lock.release()
        self.passive_port = None
        print("[-] No available passive ports left")
        return None

    def release_passive_port(self):
        if DEBUG:
            print("[debug] Releasing passive port ",self.passive_port)
        self.meltingpot.lock.acquire()
        self.meltingpot.passive_ports[self.passive_port-self.meltingpot.first_passive_port] = False
        self.meltingpot.lock.release()
        
    def PORT(self, data):
        if self.pasv_mode:
            self.servsock.close()
            self.pasv_mode = False
            
        l=data[5:].split(',')
        self.dataAddr='.'.join(l[:4])
        self.dataPort=(int(l[4])<<8)+int(l[5])
        if DEBUG:
            print("[debug] PORT: addr={0} port={1}".format(self.dataAddr, self.dataPort))
        self.conn.send(b'200 PORT command successful\r\n')
        return True

    def CWD(self, data):
        # we don't support changing directories as we want to make sure not to exit ftproot
        self.conn.sendall(b'550 No such file or directory\r\n')
        return True

    def PWD(self, data):
        self.conn.sendall(b'257 "/"\r\n')
        return True

    def CDUP(self, data):
        self.conn.sendall(b'200 Okay\r\n')
        return True

    def MKD(self, data):
        self.conn.send(b'257 Directory created.\r\n')
        return True

    def RMD(self, data):
        self.conn.send(b'450 Not allowed.\r\n')
        return True

    def DELE(self, data):
        self.conn.send(b'450 Not allowed.\r\n')
        return True

    def RNTO(self, data):
        self.conn.send(b'250 File renamed.\r\n')
        return True

    def RNFR(self, data):
        self.conn.send(b'350 Ready.\r\n')
        return True

    def REST(self, data):
        self.conn.send(b'250 File position reseted.\r\n')
        return True

    def STRU(self, data):
        self.conn.send(b'200 OK\r\n')
        return True

    def MODE(self, data):
        self.conn.send(b'200 OK\r\n')
        return True

    def FEAT(self, data):
        self.conn.send(b'500 Unsupported command\r\n')
        return True

    def AUTH(self, data):
        self.conn.send(b'500 Unsupported command\r\n')
        return True
        
    
    def start_datasock(self):
        if self.pasv_mode:
            self.datasock, addr = self.servsock.accept()
            if DEBUG:
                print("[debug] start_datasock() passive mode: accepting incoming connection on port {0}: client={1}:{2}".format(self.passive_port, addr[0], addr[1]))
        else:
            # in active mode, client should normally have set address and port to use
            # with PORT command, which sets dataAddr and dataPort
            # this is not a secure option... but some old FTP clients use it

            # In our honeypot, we won't even care to try and make this work
            # self.datasock=socket.socket(socket.AF_INET,socket.SOCK_STREAM)
            # self.datasock.connect((self.dataAddr,self.dataPort))
            if DEBUG:
                print("[debug] start_datasock(): active mode not supported")
            raise Exception("Active mode not supported")

    def stop_datasock(self):
        if DEBUG:
            print("[debug] stop_datasock()")
        self.datasock.close()
        if self.pasv_mode:
            if DEBUG:
                print('[debug] closing servsock')
            self.servsock.close()
            self.release_passive_port()

    def LIST(self, data):
        self.conn.send(b'150 Opening ASCII mode data connection.\r\n')

        try:
            self.start_datasock()
        except Exception as e:
            if DEBUG:
                print("[debug] LIST(): opening data sock error: ",e)
            self.conn.sendall(b'425 Connection failed\r\n')
            return False
                
        try:
            for t in os.listdir(self.meltingpot.ftproot):
                k=self.toListItem(os.path.join(self.meltingpot.ftproot,t))
                self.datasock.send(bytes(k+'\r\n', 'utf-8'))
            message='226 Directory send OK'
            self.stop_datasock()    
        except Exception as e:
            traceback.print_exc()
            if DEBUG:
                print("[debug] LIST() error: data={0} e={1}".format(data,e))
            message='451 Directory KO'
        
        self.conn.send(bytes(message+'\r\n', 'utf-8'))
        return True

    def NLST(self,data):
        return self.LIST(data)
        
    def toListItem(self,fn):
        st=os.stat(fn)
        fullmode='rwxrwxrwx'
        mode=''
        for i in range(9):
            mode+=((st.st_mode>>(8-i))&1) and fullmode[i] or '-'
        d=(os.path.isdir(fn)) and 'd' or '-'
        ftime=time.strftime(' %b %d %H:%M ', time.gmtime(st.st_mtime))
        return d+mode+' 1 root root '+str(st.st_size)+ftime+os.path.basename(fn)

    def openFile(self, data, themode='r'):
        try:
            filename=os.path.join(self.meltingpot.ftproot,data[5:-2])
            mode = themode
            if self.binary:
                mode += 'b'
            f=open(filename,mode)
            return f
        except Exception as e:
            if DEBUG:
                print("[debug] Exception in openFile: filename={0} mode={1} file exception={2}".format(filename,mode, e))
            self.conn.send(b'451 Cannot perform operation\r\n')
            return None
    
    def RETR(self, data):
        fi = self.openFile(data, 'rb')
        if fi == None:
            return False

        # we read the file and send it over data socket connection
        self.conn.send(b'150 Opening data connection for RETR.\r\n')
        d= fi.read(1024)
        self.start_datasock()
        while d:
            self.datasock.send(d)
            d=fi.read(1024)
            
        fi.close()
        self.stop_datasock()
        self.conn.send(b'226 Transfer complete.\r\n')
        self.log("Downloaded file {0}".format(data[5:-2]))
        return True

    def STOR(self,data):
        if not self.meltingpot.enable_upload:
            self.conn.send(b'450 Not allowed.\r\n')
            return True
            
        fo = self.openFile(data,'w')
        if fo == None:
            # An exception occurred with that file
            return False

        try:
            tmpname = os.path.join(self.meltingpot.upload_dir, '.tmp')
            copy = open(tmpname, 'wb')
            self.conn.send(b'150 Opening data connection.\r\n')
            self.start_datasock()
            sha256_hash = hashlib.sha256()
            while True:
                d=self.datasock.recv(1024).decode()
                if not d: break
                sha256_hash.update(bytes(d, 'utf-8'))
                fo.write(d)
                copy.write(bytes(d, 'utf-8'))
            fo.close()
            copy.close()
            self.stop_datasock()

            # move tmp file
            realname = sha256_hash.hexdigest()
            self.log("Uploaded file {0}".format(realname))
            os.rename(tmpname, os.path.join(self.meltingpot.upload_dir, realname))

        except Exception as e:
            print("[ERROR] cannot open copy file: upload_dir={0} e={1}".format(self.meltingpot.upload_dir, e))
            traceback.print_exc()
            return False

        self.conn.send(b'226 Transfer complete.\r\n')
        return True

class meltingpot:
    def __init__(self, configfile=CONFIG_FILE):
        self.configfile = configfile
        self.configparser = configparser.ConfigParser()
        self.configparser.read(configfile)

        self.public_ip = self.configparser.get('general', 'public_ip')
        self.host = self.configparser.get('general','host', fallback='127.0.0.1')
        self.port = self.configparser.getint('general', 'port', fallback='2221')
        self.banner = self.configparser.get('general', 'banner', fallback='220 FTP Ready')
        self.system = self.configparser.get('general', 'system', fallback='215 Unix')
        self.logfile = self.configparser.get('general', 'logfile', fallback='meltingpot.log')
        self.creds = self.configparser.get('general', 'credentials_file', fallback='creds.cfg')
        self.ftproot = self.configparser.get('general', 'ftproot',fallback='./ftproot')
        self.enable_upload = self.configparser.getboolean('general', 'enable_upload', fallback=True)
        
        self.upload_dir = self.configparser.get('general', 'upload_dir',fallback='./uploads')
        self.first_passive_port = self.configparser.getint('general', 'first_passive_port', fallback=30000)
        self.nb_passive_ports = self.configparser.getint('general','nb_passive_ports', fallback=9)

        if self.enable_upload:
            assert os.path.isdir(self.upload_dir), "[ERROR] Please create {0} directory".format(self.upload_dir)
        assert os.path.isdir(self.ftproot), "[ERROR] ftproot directory does not exist: {0}".format(self.ftproot)
        assert os.path.exists(os.path.dirname(self.logfile)), "[ERROR] Path for logfile does not exist: {0}".format(self.logfile)
            
        self.load_allowed_credentials(self.creds)
        self.init_passive_ports()
        self.lock = Lock() 
        self.init_server()

    def load_allowed_credentials(self, filename):
        f = open(filename,'r')
        lines = f.read().split('\n')
        self.users = {}
        for line in lines:
            u_p  = line.split(':')
            if len(u_p) >= 2:
                username = u_p[0]
                password = u_p[1]
                self.users[username] = password

    def init_passive_ports(self):
        # this table records used ports
        if DEBUG:
            print("[debug] Initializing passive port table...")
        self.passive_ports = []
        for port in range(0, self.nb_passive_ports):
            self.passive_ports.append(False)
        
    def init_server(self):
        self.s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        self.s.bind((self.host, self.port))
        self.s.listen(30) # backlog
        print("[+] Meltingpot is running on port {0}".format(self.port))
        while True:
            conn, addr = self.s.accept()
            print("Incoming connection from IP: {0}:{1}".format(addr[0], addr[1]))
            conn.sendall(bytes(self.banner+'\n', 'utf-8'))
            session = uuid.uuid4().hex
            FtpServerThread(conn, addr, session, self).start()

if __name__ == '__main__':
    melting = meltingpot()
    
