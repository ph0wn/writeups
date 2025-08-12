import argparse
import struct
import time
from gattlib import GATTRequester
from Crypto.Cipher import AES
import time

'''
Solution to CTF challenge

__author__ = "@cryptax"

Requirements: gattlib and bluetooth dev libs
sudo apt-get install libglib2.0-dev python-setuptools python-pip \
g++ libbluetooth-dev libboost-python-dev libboost-thread-dev
sudo pip install gattlib

Normally run as:
$ sudo python soluce.py

Beware: only one connection at a time on the toothbrush
=> if the app is connected, this program won't work. Stop the app first.

Troubleshooting:
  "Channel or attrib not ready" -> make sure you don't have another program (smartphone?)
                                                    using BLE and connected to the toothbrush
  "Device busy" => sudo bccmd -d hci0 warmreset
'''

class MyRequester(GATTRequester):
    def on_notification(self, handle, data):
        if handle == 0x25:
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
                #print '   Event: %s' % (plaintext.encode('hex'))
                # index 1 byte, duration 4 bytes, start date 6 bytes, then raw
                swapped = bytearray(plaintext)
                swapped.reverse()
                idx = swapped[0]
                duration = struct.unpack("I", swapped[1:5])[0]
                if handle == 0x25:
                    print "Event: Index = %x, Duration = %d, Date: 20%02x-%x-%x at %02x:%02x:%02x" % (idx, duration, swapped[10], swapped[9], swapped[8], swapped[7], swapped[6], swapped[5])
                    encoded_flag = "%02x%02x%02x%02x%02x" % (swapped[10], swapped[9], swapped[8], swapped[7], swapped[6])
                    print "Decoded flag: ", encoded_flag.decode('hex')


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


def get_arguments():
    parser = argparse.ArgumentParser(description='Possible solution to the toothbrush challenge at ph0wn', prog='soluce.py')
    parser.add_argument('-v', '--verbose', help='various debug messages', action='store_true')
    parser.add_argument('-t', '--target', help='MAC address of the device', default='00:07:80:20:B2:41', action='store')
    args = parser.parse_args()
    return args
        
if  __name__ == '__main__':
    args = get_arguments()
    brush = ToothBrush(address=args.target, verbose=args.verbose)
    if not brush.is_connected():
        brush.connect(verbose=args.verbose)

    # enable notifications so we see what happens
    brush.enable_event_notif()

    index = int(raw_input("Start index to read at (format: hex, without 0x): 0x"), 16)

    for i in range(index, index+5):
        brush.query_event_idx('%02x' % (i), verbose=args.verbose)

    if brush.is_connected():
        brush.disconnect(verbose=args.verbose)

