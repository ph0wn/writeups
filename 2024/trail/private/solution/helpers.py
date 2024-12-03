import struct

##### HELPER FUNCTIONS #####

def p64(value):
    return(struct.pack("<Q", value)) # little byte order

def p32(value):
    return(struct.pack("<I", value)) # little byte order

def p16n(value):
    return(struct.pack(">H", value)) # big/network byte order

def auto_int(x):
    return(int(x, 0))

