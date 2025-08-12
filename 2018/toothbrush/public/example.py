import argparse
from gattlib import GATTRequester
import struct

'''
-------------------------------------------------------------
Ph0wn 2018 - Toothbrush challenge
Example code by @cryptax
September 18, 2018
-------------------------------------------------------------

REQUIREMENTS:
- Linux
- bluetooth dev libraries
- gattlib: sudo pip install gattlib

Example:
  sudo apt-get install libglib2.0-dev python-setuptools python-pip \
     g++ libbluetooth-dev libboost-python-dev libboost-thread-dev
  sudo pip install gattlib

RUN:

$ sudo python example.py

TROUBLESHOOTING:

- Only one connection at a time on the toothbrush
- "Channel or attrib not ready": make sure you don't have another program (smartphone?) using BLE and connected to the toothbrush
- "Device busy" => sudo bccmd -d hci0 warmreset

'''

class MyRequester(GATTRequester):
    def on_notification(self, handle, data):
        print "Notification on handle: 0x%x" % (handle)
        if handle == 0x3e:
            print "Received button state notification: %s" % (data.encode('hex'))


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

    def enable_notif(self, handle):
        ''' low level function to enable notifications for a given handle '''
        hexstring = str(bytearray([01, 00]))
        notif_handle = handle
        self.req.write_by_handle(notif_handle, hexstring)

    def enable_button_notif(self, verbose=False):
        self.enable_notif(0x003f)
        if verbose:
            print "[+] Will receive button state notifications"

def get_arguments():
    parser = argparse.ArgumentParser(description='Example to discuss with Ph0wn toothbrush', prog='example.py')
    parser.add_argument('-v', '--verbose', help='various debug messages', action='store_true')
    parser.add_argument('-m', '--mac', help='MAC address of the device', action='store')
    args = parser.parse_args()
    return args
        
if  __name__ == '__main__':
    args = get_arguments()
    brush = ToothBrush(address=args.mac, verbose=args.verbose)
    if not brush.is_connected():
        brush.connect(verbose=args.verbose)

    # enable notifications so we see what happens
    brush.enable_button_notif(verbose=args.verbose)

    raw_input("Press ENTER to EXIT - otherwise, just use the toothbrush")

    if brush.is_connected():
        brush.disconnect(verbose=args.verbose)

