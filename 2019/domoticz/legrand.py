#!/usr/bin/env python3

from http.server import HTTPServer, BaseHTTPRequestHandler
from datetime import datetime, timezone
import random
import os
import logging
from logging.handlers import RotatingFileHandler

'''
This is a fake ECOCOMPTEUR LEGRAND
http://www.admin.legrandoc.com/files/documents/LE07197AD.pdf
'''

PORT = 20000
DEBUG_LOGS = '/tmp/legrand.logs'
LOG2_FILE = './log2.csv'
MAX_SIZE = 1024*1024*1024*2
FLAG_MESSAGE = 'ph0wn flag at http://10.210.17.66:20000/n1ceflag '

# global variable
data2_index = 0
data1_index = 0
data1_incremented = False

logging.basicConfig(format='[%(name)s] %(asctime)s %(message)s', datefmt='%m/%d/%Y %I:%M:%S', level=logging.DEBUG, handlers=[RotatingFileHandler(DEBUG_LOGS, maxBytes=MAX_SIZE )])
logger = logging.getLogger('legrand')
logger.debug('Logger configured')

class SimpleHTTPRequestHandler(BaseHTTPRequestHandler):

    def do_GET(self):
        #print("Incoming Path: ", self.path)
        #print("Incoming Headers: ", self.headers)

        if self.path == '/inst.json':
            self.inst_json()
        elif self.path == '/log2.csv':
            self.log2_csv()
        elif self.path == '/n1ceflag':
            self.send_flag()
        else:
            logger.warn('File Not Found: %s' % self.path)
            self.send_error(404,'File Not Found: %s' % self.path)

        return

    def inst_json(self):
        # creates a fake inst.json
        self.send_response(200)
        self.send_header('Content-type', 'application/json')
        self.end_headers()

        current_time = datetime.now()

        '''
        We have two different modes.
        The normal mode, where 
        The flag mode, 
        '''
        global data1_index
        global data1_incremented
        global data2_index

        # Domoticz queries the sensor every 10 seconds (quick loop)
        # However, it only displays points every 5 minutes (long loop)
        # We are going to send the flag on the long loop on data1
        # and on the quick loop on data2
        
        data1 = ord(FLAG_MESSAGE[data1_index])
        if current_time.minute % 5 == 0:
            if not data1_incremented:
                data1_incremented = True
                data1_index = data1_index + 1
        else:
            data1_incremented = False

        if data1_index >= len(FLAG_MESSAGE):
            data1_index = 0

        data2 = ord(FLAG_MESSAGE[data2_index])
        data2_index = data2_index + 1
        if data2_index >= len(FLAG_MESSAGE):
            data2_index = 0
            
        logger.debug("[+] data1={0} index={1}, data2={2} index={3}".format(data1, data1_index, data2, data2_index))
        
        json_content = '"data1" : {0}, "data2" : {1}, "data3": 30.0, "data4": 40.0, "data5": 50.0, "data6": 60.0, "data6m3":0.0, "data7": 70.0, "data7m3": 0.0, "heure": {2}, "minute" : {3}, "Date_Time" : {4}'.format(data1, data2, current_time.hour, current_time.minute, current_time.timestamp())
        json_content = '{'+json_content+'}'
        self.add_log2_line(data1, data2, current_time)
        self.wfile.write(bytes(json_content, "utf8"))
        return

    def add_log2_line(self, data1, data2, current_time):
        log2 = open(LOG2_FILE, 'a')
        line = "{0};{1};{2};{3};{4};".format(current_time.day, current_time.month, current_time.year, current_time.hour, current_time.minute)
        line = line + "0;0;" # energie_tele_info, prix_tele_info
        line = line + "{0};{1};".format(data1, data1*0.1287) # energie_circuit1, prix_circuit1
        line = line + "{0};{1};".format(data2, data2*0.1287) # energie_circuit2, prix_circuit2
        line = line + "0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0\r\n"
        log2.write(line)
        log2.close()
        return

    def log2_csv(self):
        # creates a fake log2.csv
        self.send_response(200)
        self.send_header('Content-type', 'text/csv')
        self.end_headers()
        log2 = open(LOG2_FILE, 'r')
        contents = log2.read()
        log2.close()
        self.wfile.write(bytes(contents, "utf8"))

        # erase log2.csv when it gets too big
        statinfo = os.stat(LOG2_FILE)
        if statinfo.st_size > MAX_SIZE:
            logger.warn("[+] File {0} has reached max capacity ({1} bytes): erasing it".format(LOG2_FILE, statinfo.st_size))
            os.remove(LOG2_FILE)
            
            
        return 
        
    def send_flag(self):
        self.send_response(200)
        self.send_header('Content-type', 'text/html')
        self.end_headers()
        self.wfile.write(bytes("<html><body>flag1: ph0wn{h0w_about_using_a_candle_instead}</body></html>", "utf8"))
        logger.debug("[+] Somebody got the flag! Client address: {0}".format(self.client_address))
        return 
    

def run():
    server_address = ('', PORT)
    httpd = HTTPServer(server_address, SimpleHTTPRequestHandler)
    logger.debug("[+] Fake LeGrand EcoCompteur serving on port {0}".format(PORT))
    httpd.serve_forever()

if __name__ == "__main__":
    run()    
