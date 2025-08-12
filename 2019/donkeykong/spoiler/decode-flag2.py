# this is the 'encoded' content of the hacked _et.bin at 0xf70
memory = [ 0x1f, 0x1e, 0x20, 0x24, 0x18, 0x13, 0x23, 0x19, 0x15, 0x1e, 0x2b, 0x3b, 0x25, 0x11, 0x19, 0x1c, 0x1e, 0x27, 0x16, 0x2c, 0x20, 0x2b, 0x00, 0x18, 0x20, 0x3c, 0x11, 0x15, 0x11, 0x1c, 0x34, 0x17, 0x49, 0x4b, 0x45, 0x47, 0x41, 0x4d ]

# decode table for Donkey Kong characters
# see https://github.com/furrykef/dkdasm/blob/master/dkong.asm lines 818 - 905
table = []

for i in range(0, 10):
    table.append('%d' % (i))

for i in range(10, 0x10):
    table.append('UNKNOWN')

table.append(' ')

for i in range(ord('A'), ord('Z') +1):
    table.append(chr(i))

table.append('.')
table.append('-')
table.append('high -')
table.append(':')
table.append('high -')
table.append('<')
table.append('>')
table.append('I for one')
table.append('II for second')
table.append('=')
table.append('-')
table.append('!!')
table.append('!!')
table.append('!')
table.append('!')
table.append("'")
table.append('"')
table.append('"')
table.append("skinny quote marks")
table.append("L shape right bottom")
table.append("L shape right top")
table.append("L shape")
table.append("L shape left top")
table.append(".")
table.append(',')
table.append("RUB END")
table.append("RUB END")
table.append("RUB END")
table.append("RUB END")
table.append("RUB END")
table.append("COPYRIGHT LOGO")
table.append("COPYRIGHT LOGO")
table.append("some logo")

# decode flag from memory
try: 
    for i in range(1, 32):
        offset=0x30+(i^0x55)-0x70
        print "Level={0} offset=0x{1:02X} memory=0x{2:02X} decoded={3}".format(i, offset, memory[offset], table[memory[offset]])
except IndexError:
    print "Offset=",hex(offset)
    print memory[offset]
    

    
