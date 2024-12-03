from flask import Flask, jsonify
import json, base64
from Crypto.Cipher import AES
import logging

logging.basicConfig(level=logging.DEBUG, format='%(asctime)s %(levelname)s : %(message)s')
app = Flask(__name__)

# Coordinates of geostationary satellites
satellite_data = [
    {"longitude": 45.0, "latitude": 30.0, "altitude": 36000},
    {"longitude": 50.0, "latitude": 32.0, "altitude": 36000},
    {"longitude": 55.0, "latitude": 35.0, "altitude": 36000},
    {"longitude": 60.0, "latitude": 38.0, "altitude": 36000},
    {"longitude": 65.0, "latitude": 40.0, "altitude": 36000}
]

@app.route('/')
def index():
    app.logger.info('index()')
    return 'No such satellite'

@app.route('/satellite1')
def get_satellite1():
    app.logger.info('get_satellite1()')
    return encrypt_satellite_data(satellite_data[0])

@app.route('/satellite2')
def get_satellite2():
    app.logger.debug('get_satellite2()')
    return encrypt_satellite_data(satellite_data[1])

@app.route('/satellite3')
def get_satellite3():
    return encrypt_satellite_data(satellite_data[2])

@app.route('/satellite4')
def get_satellite4():
    return encrypt_satellite_data(satellite_data[3])

@app.route('/satellite5')
def get_satellite5():
    return encrypt_satellite_data(satellite_data[4])

@app.route('/we_like_satellite1337')
def get_satellite1337():
    app.logger.debug('/we_like_satellite1337')
    try:
        with open('./FLAG_AND_NEXT', 'r') as file:
            content = file.read()
        app.logger.info('Got the flag!')    
        return content
    except Exception as e:
        return "An error occurred: please contact an organizer: "+str(e)
    

def pad(byte_array:bytearray, block_size=16):
    # For such algorithms, the method shall be to pad the input at the trailing end with k - (l mod k) octets all having value k - (l mod k), where l is the length of the input.
    # Java calls this "PKCS5Padding", but it's PKCS7 actually
    # https://crypto.stackexchange.com/questions/9043/what-is-the-difference-between-pkcs5-padding-and-pkcs7-padding
    pad_len = block_size - len(byte_array) % block_size
    return byte_array + (bytes([pad_len]) * pad_len)

# Hardcoded encryption key
def encrypt_satellite_data(data, encryption_key = b'PicoSt*r++Caviar', iv= b'Sixteen byte IV!'):
    # Convert data to JSON string
    json_data = json.dumps(data)
    padded = pad(json_data.encode('utf-8'))
    app.logger.debug(f'padded JSON data: {padded}')

    # Encrypt the data using AES CBC
    cipher = AES.new(encryption_key, AES.MODE_CBC, iv=iv)
    ciphertext = cipher.encrypt(padded)

    # Encode the ciphertext in base64
    encrypted_data = base64.b64encode(ciphertext)

    app.logger.debug(f'Base64 response: {encrypted_data}')
    return encrypted_data

if __name__ == '__main__':
    app.run(debug=False)
