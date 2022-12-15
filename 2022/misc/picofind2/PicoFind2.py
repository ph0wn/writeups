#!/usr/bin/env python
# -*- coding: utf-8 -*-

import serial

BAUDRATE = 115200
COM_PORT = '/dev/tty.usbserial-A50285BI'  # macOS
# COM_PORT = '/dev/ttyS1'  # Linux
# COM_PORT = 'COM4'  # Windows


if __name__ == '__main__':
    with serial.Serial(COM_PORT, BAUDRATE) as ser:
        index = 0
        while True:
            input(f"Press Enter to display image # {index}...")
            frame = b'\x5A\xA5\x04\x80\x03\x00%c' % index
            ser.write(frame)
            index += 1
