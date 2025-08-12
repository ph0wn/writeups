#!/usr/bin/env python
# Author: @cryptax

def decode(buffer, position=0x42b8,level=0x00):
    pacman_tile = ' ABCDEFGHIJKLMNOPQRSTUVWXYZ!'
    character_address = position+level-0x380 - 0x3000
    #print("Reading at 0x%0x..." % character_address)

    try:
        if ((ord(buffer[character_address]) ^ level) & 0x07) != 0:
            return ' '

        decoded = ord(buffer[character_address]) >> 3
        assert decoded < len(pacman_tile), "Beyond letters: decoded=0x%02x position=0x%02x level=%d" % (decoded, position, level)
        return pacman_tile[decoded]
    except:
        return ' '

def read_msg(buffer, addresses, level):
    msg = ''
    for video_address in addresses:
        msg = msg +  decode(b, position=video_address,level=level)
    return msg

def read_lower_msg(buffer, level=0):
    return read_msg(buffer, addresses=range(0x42f8, 0x4118, -0x20), level=level)

def read_upper_msg(buffer, level=0):
    return read_msg(buffer, addresses=range(0x4306, 0x40a6, -0x20), level=level)

def print_entire_screen(buffer, level=0):
    print('-'*0x1b)
    for lines in range(0x43a1,0x43bf):
        addresses=range(lines, lines-0x360, -0x20)
        #    print '0x%02X - 0x%02X' % (lines, lines-0x360)
        print(read_msg(buffer, addresses, level=level))
    print('-'*0x1b)

if __name__ == '__main__':
    b = open('../puckman/namcopac.6j','rb').read()

    for level in range(0,8):
        print("Level %d -----------" % (level+1))
        print(read_upper_msg(b,level=level))
        print(read_lower_msg(b,level=level))

    print("Screen of Level 5 ----------------")
    print_entire_screen(b, level=4)

    print("Screen of Level 8 ---------------")
    print_entire_screen(b, level=7)


