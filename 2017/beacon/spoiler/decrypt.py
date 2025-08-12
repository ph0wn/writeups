from Crypto.Cipher import AES

key = b'KyloRenIsBadJedi'
algo = AES.new(key, AES.MODE_ECB)

cleartext = 'Good Padawan!!!!'
encrypted = algo.encrypt(cleartext)
print "Hex result: ", encrypted.encode('hex')

decrypted = algo.decrypt(encrypted)
print "Check decryption: ",decrypted
