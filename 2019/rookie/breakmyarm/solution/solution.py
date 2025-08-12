#!/usr/bin/env python3

hardcoded = list(bytes.fromhex('6c642c736a776b715f645b756b715b5e6e6b67615b69755b5d6e6979'))
print("Decoded password: ", ''.join([ chr(x+4) for x in hardcoded ]))
