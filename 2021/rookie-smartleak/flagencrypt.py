#!/usr/bin/env python3
from Crypto.Cipher import AES

secretkey = 'ThePerfectRookie'
flag = 'ph0wn{congrats_u_are_a_rookie++}'
cipher = AES.new(secretkey, AES.MODE_ECB)
ciphertext = cipher.encrypt(flag)
print("Ciphertext: {}".format(ciphertext.hex()))

# decrypting
plain = cipher.decrypt(ciphertext)
assert(plain == bytes(flag, 'utf-8')), "Encryption/Decryption error"
print("Decrypted OK")

