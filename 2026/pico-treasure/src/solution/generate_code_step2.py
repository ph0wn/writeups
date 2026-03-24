#!/usr/bin/env python3
import time

MOD_14 = 100_000_000_000_000

K1 = 0xC0B41E7ED15EA5E1
K2 = 0xBADC0FFEE0DDF00D
K3 = 0x1EA57EADBEAD1DEA

def generate_code(minutes: int) -> int:
    x = minutes ^ K1
    x = (x * K2) & 0xFFFFFFFFFFFFFFFF
    x = ((x << 5) | (x >> (64 - 5))) & 0xFFFFFFFFFFFFFFFF
    x = (x + K3) & 0xFFFFFFFFFFFFFFFF
    return x % MOD_14

def format_code(minutes: int) -> str:
    return f"{generate_code(minutes):014d}"

if __name__ == "__main__":
    minutes = int(time.time() // 60)
    print("Current minutes:", minutes)
    print("Code:", format_code(minutes))
