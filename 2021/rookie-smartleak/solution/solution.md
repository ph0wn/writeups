# Step  1: getting the key

The key is transfered with the application bus. Simply concatenate all binary values, and you should obtain:

"ThePerfectRookie"

hex: ['0x54', '0x68', '0x65', '0x50', '0x65', '0x72', '0x66', '0x65', '0x63', '0x74', '0x52', '0x6f', '0x6f', '0x6b', '0x69', '0x65']

binayr: ['01010100', '01101000', '01100101', '01010000', '01100101', '01110010', '01100110', '01100101', '01100011', '01110100', '01010010', '01101111', '01101111', '01101011', '01101001', '01100101']

# Step 2: getting the cyphered text

The text is transferred with a serie of Write/Read operations on the interface bus.

binary: ['11010010', '01001110', '10010000', '00011110', '00011111', '01010000', '01010101', '01110000', '11110111', '00100111', '11000110', '11001110', '01011101', '10010001', '10000011', '10111101', '00101001', '01101100', '01010010', '10101111', '11011111', '11111010', '10110100', '10101000', '00111010', '11110111', '00111010', '01010011', '11111010', '01010101', '00011100', '00000001']

hex: d24e901e1f505570f727c6ce5d9183bd296c52afdffab4a83af73a53fa551c01

Decipher this with AES, ECB mode.

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