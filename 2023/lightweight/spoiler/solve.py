# Use Python2.7 for this implementation...
from pypresent import Present 

key = '003180e008219fe50333'
ciphertext = '47c8a2e0bade478e23290dec2a116f4b7a273d9516fe45d1b5fe2e92916e2ef1e3e219b38cd0e687'

# check lengths
assert(len(key) / 2 == 10)
assert(len(ciphertext) / 2 == 40)

# decrypt
cipher = Present(key.decode('hex'))
plaintext = ''
for i in range(0, 80, 16):
    plaintext = plaintext + cipher.decrypt(ciphertext[i:i+16].decode('hex'))

# display    
print(plaintext)
    


