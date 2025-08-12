#!/usr/bin/env python3

import argparse
import requests
import socket
import time

def main(args):
    requests.post("http://{:s}/do_cmd.htm".format(args.plug_address), data = {"CMD": "SYS", "GO": "admin.htm", "SET0": "16843264={:s}".format(args.password)})
    time.sleep(1)
    telnet = socket.create_connection((args.plug_address, 23))
    telnet.send(b"admin\r\n")
    time.sleep(0.1)
    telnet.send(args.password.encode(encoding = "ISO-8859-1") + b"\r\n")
    time.sleep(0.1)
    telnet.send(b"cd cfg\r\n")
    time.sleep(0.1)
    telnet.send(b"set SYS_NAME " + args.flag.encode(encoding = "ISO-8859-1") + b"\r\n")
    time.sleep(0.1)
    telnet.send(b"cd ..\r\n")
    time.sleep(0.1)
    telnet.send(b"logout\r\n")
    time.sleep(0.1)
    print("Challenge is ready!")

    
    
def parse_args():
    parser = argparse.ArgumentParser(description = "Reset Meross MSS310 to initial challenge state")
    parser.add_argument("--plug-address", default = "10.10.10.1", help = "IP address of plug")
    parser.add_argument("--password", default = "vane2aelohNg", help = "Password for telnet interface")
    parser.add_argument("--flag", default = "ph0wn{kahZoo0w}", help = "Flag to set")
    return parser.parse_args()


if __name__ == "__main__":
    main(parse_args())
