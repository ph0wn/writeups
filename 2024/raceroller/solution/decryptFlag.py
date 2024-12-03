import base64
from Crypto.Cipher import AES
from Crypto.Util.Padding import pad, unpad

encryptedFlag = b'rRX0o5VF6Rlz6aHlL+qH9jUtobYXmVcVAfq72Z4nOGA='
key = b'7529381058256418'

# decode the key
unb64_encrypted_flag = base64.b64decode(encryptedFlag)

# decrypt
cipher = AES.new(key, AES.MODE_ECB)
decrypted_padded_text = cipher.decrypt(unb64_encrypted_flag)
decrypted = unpad(decrypted_padded_text, AES.block_size).decode('utf-8')
print(f'Decrypted={decrypted}')
