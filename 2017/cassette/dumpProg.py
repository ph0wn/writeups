#!/usr/bin/env python2

import sys
import struct

def usage(fname):
    print "Usage: %s <file>" % fname
    sys.exit(1)

def writeToFile(f, size):
    with open("prog.dump", "wb") as f2:
        #f2.write(struct.pack("BBBB", 16, 16, 16, 24))
        f2.write(f.read(size))

def decodeFmt(f, size):
    print "fmt chunk"
    n = struct.unpack("HHIIHHH", f.read(18))
    print "    AudioFormat: %d" % n[0]
    print "    NbrCanaux: %d" % n[1]
    print "    Frequence: %d" % n[2]
    print "    BytePerSec: %d" % n[3]
    print "    BytePerBloc: %d" % n[4]
    print "    BitsPerSample: %d" % n[5]
    print "    ExtensionSize: %d" % n[6]
    
    if size > 18:
        print "    !!! More data !!!"
        f.seek(size-18, 1)

def decodeData(f, size):
    print "data chunk"
    writeToFile(f, size)
        

def decodeFact(f, size):
    print "fact chunk"
    n = struct.unpack("I", f.read(4))[0]
    print "    SamplesPerChannel: %d" % n
    if size > 18:
        print "    !!! More data !!!"
        f.seek(size-4, 1)

def decodeChunk(f):
    formatBlock = f.read(4)
    size = struct.unpack("I", f.read(4))[0]
    if formatBlock == "fmt ":
        decodeFmt(f, size)
    elif formatBlock == "data":
        decodeData(f, size)
    elif formatBlock == "fact":
        decodeFact(f, size)
    else:
        print "Unknown chunk type: '%s'" % ", ".join(hex(c) for c in struct.unpack("BBBB", formatBlock))
        f.seek(size, 1)
    return size+8

if __name__ == '__main__':
    if len(sys.argv) < 2:
        usage(sys.argv[0])

    with open(sys.argv[1], "rb") as f:
        magic = f.read(4)
        print "magic: " + magic
        size = struct.unpack("I", f.read(4))[0]

        print "size: %d" % size

        magic = f.read(4)
        print "magic: " + magic
        size -= 4

        while size > 0:
            print "size: %s" % size
            size = size - decodeChunk(f)
