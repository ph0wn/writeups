#! /usr/bin/env python
# sudo apt-get install python-serial
import serial

port = serial.Serial("/dev/ttyACM0", baudrate=9600)

while True:
    rcv = port.read(80)
    print rcv
        
