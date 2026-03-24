import hashlib
import logging
import os
import re
import json

# Create a logger for auth.py
logger = logging.getLogger(__name__)
logger.setLevel(logging.DEBUG)
if not logger.handlers:
    ch = logging.StreamHandler()
    formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
    ch.setFormatter(formatter)
    logger.addHandler(ch)

ALPH = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"  # no 0/O/1/I

def compute_authcode(challenge, badge_id='', username='deckhand'):
    # hash input
    tohash = f'{username}-{challenge}-{badge_id}'
    logger.debug(f'challenge={challenge} len={len(challenge)} tohash={tohash}')
    d = hashlib.sha256(tohash.encode()).digest()
    logger.debug(f'hexdigest={d.hex()}')

    # create authorization code
    authcode = []
    for i in range(16):
        p = (i * 7 + 11) & 31
        authcode.append(ALPH[d[p] % len(ALPH)])
    s = ''.join(authcode)
    show_auth = f'{s[:4]}-{s[4:8]}-{s[8:12]}-{s[12:]}'
    logger.debug(f'computed code={show_auth} len(code)={len(show_auth)}')
    return show_auth

