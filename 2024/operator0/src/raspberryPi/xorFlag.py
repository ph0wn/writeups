#! /usr/sbin/env python3

import sys
import base64 as b64

flag = "ph0wn{kur0icroco_sh0uld_h4ve_us3d_a_m0re_subt1le_appr0ach}"
key = "ph0wn24Operator0X0RKey"

def xor(data:str, key:str) -> bytes:
    key = key * (len(data) // len(key)) + key[:len(data) % len(key)]
    return bytes([a ^ b for a, b in zip(data, key.encode())])

xor_flag = xor(flag.encode(), key)
xor_flag = b64.b64encode(xor_flag).decode()

print(f"Encrypted flag: {xor_flag}")

# break the b64 into 2 parts
print(f"part1: {xor_flag[:len(xor_flag)//2]}")
print(f"part2: {xor_flag[len(xor_flag)//2:]}")
 

