#!/usr/bin/env python3

plaintext = b'vroum_crocobeatsmario\x00'
key = 0x45

def xor(data, key):
    result = bytearray()
    for byte in data:
        result.append(byte ^ key)
    return bytes(result)

print("Plaintext:", plaintext)
encrypted_text = xor(plaintext, key)

print("char encrypted_flag [] = {", end='');
for b in encrypted_text:
    print(f'{hex(b)}, ', end='')
print("};")
print("Decrypted:", xor(encrypted_text, key))
