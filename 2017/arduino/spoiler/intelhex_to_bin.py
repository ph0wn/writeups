import bincopy
import sys

f = bincopy.BinFile()
f.add_ihex_file(sys.argv[1])
#print(f.as_ihex())
print(f.as_binary())
