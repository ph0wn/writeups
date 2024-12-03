from Crypto.Cipher import AES
import logging
import json
import base64

logging.basicConfig(level=logging.DEBUG, format='%(asctime)s %(levelname)s : %(message)s')
logger = logging.getLogger()

def pad(byte_array:bytearray, block_size=16):
    # For such algorithms, the method shall be to pad the input at the trailing end with k - (l mod k) octets all having value k - (l mod k), where l is the length of the input.
    # Java calls this "PKCS5Padding", but it's PKCS7 actually
    # https://crypto.stackexchange.com/questions/9043/what-is-the-difference-between-pkcs5-padding-and-pkcs7-padding
    pad_len = block_size - len(byte_array) % block_size
    return byte_array + (bytes([pad_len]) * pad_len)

# Hardcoded encryption key
def encrypt_satellite_data(data, encryption_key = b'PicoSt*r++Caviar', iv= b'Sixteen byte IV!'):
    padded = pad(data.encode('utf-8'))
    logger.debug(f'padded data: {padded}')

    # Encrypt the data using AES CBC
    cipher = AES.new(encryption_key, AES.MODE_CBC, iv=iv)
    ciphertext = cipher.encrypt(padded)

    # Encode the ciphertext in base64
    encrypted_data = base64.b64encode(ciphertext)

    logger.debug(f'Base64 response: {encrypted_data}')
    return encrypted_data

encrypt_satellite_data('we_like_satellite1337')
