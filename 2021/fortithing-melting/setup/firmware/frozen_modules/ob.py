import ucryptolib

def encrypt(plain):
    key = 44202778927232420267628379558550793048
    enc = ucryptolib.aes(key.to_bytes(16, 'big'), 1)
    plain_bytes = plain.encode()
    cipher = enc.encrypt(plain_bytes + b'\x00' * ((16 - (len(plain_bytes) % 16)) % 16))
    return cipher    

def decrypt(message):
    key = 44202778927232420267628379558550793048
    dec = ucryptolib.aes(key.to_bytes(16, 'big'), 1)
    value = dec.decrypt(message['text'])[0:message['length']].decode('utf-8')
    return value


