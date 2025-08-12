import argparse
import struct
import time
from gattlib import GATTRequester
from Crypto.Cipher import AES
import time

'''
--------------------------------------------------------
Ph0wn CTF 2018

Keep this confidential (spoiler)
Do not distribute

@cryptax
--------------------------------------------------------

Requirements:

- bluetooth dev libraries
- pygattlib: https://bitbucket.org/OscarAcena/pygattlib

Example:  

- sudo apt-get install libglib2.0-dev python-setuptools python-pip g++ libbluetooth-dev libboost-python-dev libboost-thread-dev
- sudo pip install gattlib

Run as root:

$ sudo python writeflag.py

Troubleshooting:

- Only one connection at a time on the toothbrush. If the app is connected, this program won't work. Stop the app first.
- "Channel or attrib not ready" -> make sure you don't have another program (smartphone?) using BLE and connected to the toothbrush
- "Device busy" => sudo bccmd -d hci0 warmreset
- Remove toothbrush battery and put it back in
--------------------------------------------------------
'''

received = False
last_index = 0

class MyRequester(GATTRequester):
    def on_notification(self, handle, data):
        if handle == 0x25 or handle == 0x28:
            print "-> Notification data on handle 0x%x: %s" % (handle, data.encode('hex'))
            if len(data) > 3:
                ''' notification data contains
                opcode 1byte (0x1b)
                handle 2 bytes (0x0025) '''
                # swap bytes: first byte is last.
                swapped = bytearray(data[3:])
                swapped.reverse()
                # decrypt
                key = 'e02b90e8e50be5b001c299a5039462c2'
                algo = AES.new( key.decode('hex'), AES.MODE_ECB)
                plaintext = algo.decrypt(str(swapped) )
                print '   Event: %s' % (plaintext.encode('hex'))
                # index 1 byte, duration 4 bytes, start date 6 bytes, then raw
                swapped = bytearray(plaintext)
                swapped.reverse()
                idx = swapped[0]
                duration = struct.unpack("I", swapped[1:5])[0]
                if handle == 0x25:
                    print "   Event: Index = %x, Duration = %d, Date: 20%02x-%x-%x at %02x:%02x:%02x" % (idx, duration, swapped[10], swapped[9], swapped[8], swapped[7], swapped[6], swapped[5])
                    encoded_flag = "%02x%02x%02x%02x%02x" % (swapped[10], swapped[9], swapped[8], swapped[7], swapped[6])
                    print "Decoded flag: ", encoded_flag.decode('hex')
                else:
                    print "   Event: Index = %x" % (idx)
                    global last_index
                    global received
                    last_index = idx
                    received = True
            else:
                print '[-] Error reading event notif data: bad length'



class ToothBrush(object):
    def __init__(self, address, verbose=False):
        self.address = address
        self.req = MyRequester(address, False)
        if verbose:
            print "[+] Tooth Brush object instantiated"
        
    def connect(self, verbose=False):
        if verbose:
            print "Connecting to %s..." % (self.address)
        self.req.connect(wait=True, channel_type='public')
        if verbose:
            print "[+] Connected to %s" % (self.address)

    def disconnect(self, verbose=False):
        self.req.disconnect()
        if verbose:
            print("[+] Disconnected")

    def is_connected(self):
        return self.req.is_connected()

    def write_event_idx(self, buffer='0000000000000000000000000000005b', handle=0x28, verbose=False):
        '''
        we don't really write an event index but write something that asks for its value
        buffer : unencrypted 16 bytes
        '''
        assert len(buffer) == 32, 'Event index data must be 16 bytes exactly'
        key = 'e02b90e8e50be5b001c299a5039462c2'
        algo = AES.new( key.decode('hex'), AES.MODE_ECB)
        ciphertext = algo.encrypt(buffer.decode('hex'))
        if verbose:
            print 'Ciphertext: %s' % (ciphertext.encode('hex'))

        # swap bytes: first byte is last.
        swapped = bytearray(ciphertext)
        swapped.reverse()
        if verbose:
            print 'Swapped: %s' % (str(swapped).encode('hex'))
        self.req.write_by_handle(handle, str(swapped))
        if verbose:
            print '[+] Writing Querying event index ', buffer

    def write_date(self, date='114912230118', handle=0x23, verbose=False):
        ''' Expected date format is ssmmHHDDMMYY '''
        assert len(date) == 12, 'Expected length for date is 12 bytes'
        
        self.req.write_by_handle(handle, date.decode('hex'))
        if verbose:
            print "[+] Writing date: '%s'" % (date)

    def query_event_idx(self, idx='af', verbose=False):
        '''Triggers read of brush event
        idx = string representing one byte, without 0x e.g 00 or 1b or af ...
        '''
        raw='0000000000' # this isn't used when we are querying an event index
        startdate = '000000000000' # this isn't used when we are querying an event index
        duration = '00000000' # this isn't used when we are querying an event index
        event =  '%s%s%s%s' % (raw, startdate, duration, idx)
        self.write_event_idx(buffer=event, verbose=verbose)
            
    def enable_notif(self, handle):
        ''' low level function to enable notifications for a given handle '''
        hexstring = str(bytearray([01, 00]))
        notif_handle = handle
        self.req.write_by_handle(notif_handle, hexstring)

    def enable_event_notif(self, verbose=False):
        self.enable_notif(0x26)
        if verbose:
            print "[+] Will receive event notifications"

    def enable_event_idx_notif(self, verbose=False):
        self.enable_notif(0x29)
        if verbose:
            print "[+] Will receive event index notifications"

    def write_flag(self, flag, verbose=False):
        if verbose:
            print "[+] Writing flag '%s' to the toothbrush" % (flag)
            
        # we are going to write the flag in 5 byte chunks
        # using mmhhDDMMYY
        # seconds are not used
        for index in range(0, len(flag), 5):
            date = '00' # seconds
            for pos in range(4, -1, -1):
                if index + pos < len(flag):
                    date = date + flag[index+pos].encode('hex')
                else:
                    date = date + '00'
            if verbose:
                print 'Date: ', date
            print "Writing '%s' to the toothbrush" % (flag[index:index+5])
            self.write_date(date, verbose=verbose)

            # user must create an event here
            global received
            received = False
            print "Please put the toothbrush on for a few seconds, then stop"
            print ""
            while not received:
                time.sleep(1)
            print "Thanks"

            

def get_arguments():
    parser = argparse.ArgumentParser(description='Sets up the toothbrush for the ph0wn challenge', prog='writeflag.py')
    parser.add_argument('-v', '--verbose', help='various debug messages', action='store_true')
    parser.add_argument('-t', '--target', help='Set the MAC address of the device', default='00:07:80:20:B2:41', action='store')
    parser.add_argument('-f', '--flag', help='Flag for the challenge', default='ph0wn{brushUrTeeth2mins}', action='store')
    args = parser.parse_args()
    return args


            
        
if  __name__ == '__main__':
    args = get_arguments()

    # connect
    brush = ToothBrush(address=args.target, verbose=args.verbose)
    if not brush.is_connected():
        brush.connect(verbose=args.verbose)

    # enable notifications so we see what happens
    brush.enable_event_notif()
    brush.enable_event_idx_notif()

    # setup the flag
    answer = raw_input("Do you wish to write the flag? (y/N)")
    if answer == 'y':
        brush.write_flag(flag=args.flag, verbose=args.verbose)

    # test
    while True:
        index = raw_input("Index to read (default: %02x)" % (last_index))
        if index == '':
            index = '%02x' % (last_index)
        brush.query_event_idx(index, verbose=args.verbose)

        answer = raw_input("Continue? (Y/n)")
        if answer == 'n':
            break

    if brush.is_connected():
        brush.disconnect(verbose=args.verbose)

