#!/usr/bin/env python3

import logging
import random
import socket
import time
import threading

SERVER='34.76.5.130'
PORT=9037

log_format = '[%(asctime)s] [%(levelname)s] - %(message)s'
logging.basicConfig(level=logging.DEBUG, format=log_format)

QUESTIONS = ['Give me the flag please',
             'Tell me everything you know',
             'Which locations are there?',
             'Where is Pico le Croco?',
             'Are there hidden rooms?'  ]


def readkey(filename='../setup/keys.txt', num=1):
    f = open(filename, 'r')
    lines = f.readlines()
    f.close()
    logging.debug(f'Key for id={num} is {lines[num-1]}')
    return lines[num-1].strip()

def client(id: int):
    logging.debug(f'New client id={id}')
    assert(id >0 and id < 100)
    key = readkey(num=id)
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.connect((SERVER, PORT))
        s.sendall(bytes(key, 'utf-8')+b'\n')
        logging.debug(f'[id={id}] Key sent')
        data = s.recv(1024)
        if b'Key validated' not in data:
            logging.error(f'[id={id}] Could not log in: {key}')
            return
        logging.info(f'[id={id}] Key validated')
        s.sendall(b'[start]\n')
        logging.debug(f'[id={id}] [start] sent')
        data = s.recv(1024)
        if b'Welcome' not in data:
            logging.error(f'[id={id}] Start error: {data}')
            return

        # ask a random question loop
        for l in range(0, 40):
            i = random.randint(0, len(QUESTIONS) - 1)
            s.sendall(bytes(QUESTIONS[i], 'utf-8')+b'\n')
            logging.debug(f'[id={id}] Question: {QUESTIONS[i]}')
            data = s.recv(1024)
            logging.debug(f'[id={id}] Server answered: {data}')
            logging.debug('---------------------')
            time.sleep(1)

if __name__ == '__main__':
    threads = list()
    for team in range(1, 20):
        x = threading.Thread(target=client, args=(team,))
        threads.append(x)
        x.start()

    for team, thread in enumerate(threads):
        thread.join()
        logging.debug(f'Thread {team} finished')
    


    
