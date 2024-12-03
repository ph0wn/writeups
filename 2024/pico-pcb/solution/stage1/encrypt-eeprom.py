import logging
from Crypto.Cipher import AES
import os

KEY_IV_FILE='./pcb-key'
CLEARTEXT_FILE='./cleartext'

# Set up logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

def pad(data):
    """Pads the input data to be a multiple of 16 bytes (AES block size)."""
    padding_len = AES.block_size - len(data) % AES.block_size
    padding = bytes([padding_len] * padding_len)
    logging.info(f'Padding plaintext with {padding_len} byte(s).')
    return data + padding

def to_c_array(data, label='ciphertext'):
    """Converts bytes to a C-style array representation."""
    array_str = ', '.join(f'0x{byte:02x}' for byte in data)
    return f'unsigned char {label}[] = {{{array_str}}};'

def aes_cbc_encrypt(key, iv, data):
    """Encrypts data using AES CBC mode."""
    cipher = AES.new(key, AES.MODE_CBC, iv)
    logging.info('Starting AES CBC encryption.')
    encrypted_data = cipher.encrypt(data)
    logging.info('Encryption completed.')
    return encrypted_data

def read_key_iv(file_path):
    """Reads the key and IV from a file in the format 'key: <key>' and 'iv: <iv>'."""
    logging.info(f'Reading key and IV from file: {file_path}')
    with open(file_path, 'r') as f:
        lines = f.readlines()
        key_line = [line for line in lines if line.startswith("key:")][0]
        iv_line = [line for line in lines if line.startswith("IV:")][0]
        
        # Extract key and IV strings
        key = key_line.split("key:")[1].strip()
        iv = iv_line.split("IV:")[1].strip()

        # Ensure the key is exactly 16 bytes (AES-128) and IV is a 16-byte hex string
        if len(key) != 16 or len(iv) != 16:
            logging.error('Bad length: len(key)={len(key)} len(iv)={len(iv)}')
            raise ValueError("Key/IV must be exactly 16 bytes")
        
        logging.info(f'Key: {key}, IV: {iv}')
        return key.encode(), iv.encode()

# Main logic starts here
logging.info('Setup program to encrypt what we will put in the EEPROM.')

try:
    # Read the key and IV from the pcb-key file
    key, iv = read_key_iv(KEY_IV_FILE)

    # Read cleartext from the input file
    logging.info(f'Reading input from file: {CLEARTEXT_FILE}')
    with open(CLEARTEXT_FILE, 'rb') as f:
        plaintext = f.read()

    # Pad the plaintext to ensure it's a multiple of AES block size
    padded_plaintext = pad(plaintext)

    # Encrypt the data using AES in CBC mode
    ciphertext = aes_cbc_encrypt(key, iv, padded_plaintext)
    logging.debug(f'ciphertext={ciphertext}')
    print(to_c_array(ciphertext, 'encrypted payload'))
    ciphertext = b'BEGIN ENCRYPTED STAGE1 CONTENT' + ciphertext + b'END ENCRYPTED STAGE1 CONTENT'

    # Print the result
    print("/* AES CBC Encryption */")
    print(f"/* Key: {key.decode()} */")
    print(to_c_array(key, 'key'))
    
    print(f"/* IV: {iv.decode()} */")
    print(to_c_array(iv, 'iv'))

    print('/* ciphertext */')
    print(to_c_array(ciphertext, 'ciphertext'))

except Exception as e:
    logging.error(f"An error occurred: {str(e)}")
