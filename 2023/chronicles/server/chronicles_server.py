import socket
import logging
import threading

HOST = '0.0.0.0'
PORT = 9910
PASSWORD = 'CapitaineFlam'
MAX_CONNECTIONS = 10

logging.basicConfig(level=logging.DEBUG, format='%(asctime)s %(levelname)s : %(message)s')
logger = logging.getLogger()

def handle_client(conn, addr):
    with conn:
        logger.debug(f"{addr}: connected")
        conn.sendall("Enter password: ".encode('utf-8'))
        password = conn.recv(1024).decode('utf-8').strip()
        if password == PASSWORD:
            logger.debug(f'{addr}: good password')
            conn.sendall("The flag is: ph0wn{Found_X}\nwhere X remains to find in the box of caviar\n".encode('utf-8'))
        else:
            logger.debug(f'{addr}: wrong password: {password[:len(PASSWORD)]}')
            conn.sendall("Invalid password\n".encode('utf-8'))

def main():
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as server_socket:
        server_socket.bind((HOST, PORT))
        server_socket.listen(MAX_CONNECTIONS)

        logger.debug(f"Listening on {HOST}:{PORT}...")
        while True:
            conn, addr = server_socket.accept()
            thread = threading.Thread(target=handle_client, args=(conn, addr))
            thread.start()

if __name__ == '__main__':
    main()
