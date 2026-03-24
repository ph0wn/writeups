#!/usr/bin/env python3
"""CTF Time Lock - Access Code Generator (CORRECTED)"""

import time

def generate_code(minutes_since_epoch):
    """
    Generate the 14-digit access code for given minutes since epoch.
    Returns list of 3 codes for [minute-1, minute, minute+1] to handle timing.
    """
    # Constants extracted from assembly
    XOR_CONST = 0xc0b41e7ed15ea5e1
    MUL_CONST = 0xbadc0ffee0ddf00d  # CORRECTED!
    ADD_CONST = 0x1ea57eadbead1dea
    MOD_CONST = 100000000000000
    
    codes = []
    
    for offset in [-1, 0, 1]:
        t = minutes_since_epoch + offset
        
        # XOR with magic constant
        xor_val = t ^ XOR_CONST
        
        # Multiply (128-bit operation)
        product = xor_val * MUL_CONST
        
        # Rotate right by 59 bits
        # In Python: rotate right = (val >> n) | (val << (64-n))
        # Working with 64-bit rotation
        product_64 = product & 0xFFFFFFFFFFFFFFFF
        rotated = ((product_64 >> 59) | (product_64 << 5)) & 0xFFFFFFFFFFFFFFFF
        
        # Add constant and take modulo to get 14-digit code
        code = (rotated + ADD_CONST) % MOD_CONST
        
        codes.append(f"{code:014d}")
    
    return codes


def main():
    current_time = int(time.time())
    minutes = current_time // 60
    
    print(f"Current Unix time: {current_time} seconds")
    print(f"Minutes since epoch: {minutes}")
    print(f"Seconds into current minute: {current_time % 60}")
    print()
    
    codes = generate_code(minutes)
    
    print("Valid codes (to handle timing):")
    print(f"  Previous minute: {codes[0]}")
    print(f"  Current minute:  {codes[1]}")  
    print(f"  Next minute:     {codes[2]}")
    print()
    print(f"==> ENTER THIS CODE: {codes[1]}")

if __name__ == "__main__":
    main()
