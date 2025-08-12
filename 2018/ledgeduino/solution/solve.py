#!/usr/bin/env python
from Crypto.PublicKey import RSA
from Crypto.Cipher import PKCS1_OAEP
from fractions import gcd
import hashlib

publickeyfile = 'rsa_key.pub'
faultysig = '11e8b022452ab62d73f486f4e791acbc5935f8b3fa93f5662c1371d40201b7aaade2269c6de72ca7fb5f19fe0f1c7126df0224838c1e228f71798032f246cfb22e3926c5b1682c5f066f79cc6f17e998cefdfc24e5ac8b4201965af18d3932065c52e94129c2d2e924f65c2a66e22644e0c35a24abae004efd45e705ca7b049a6ccb46cefdce6fd825b0339b5d7c883a3690349301c112400eeb27bce7932e56147c3a0c1ed19b2c65f0dcdb7f135c98c98129146ec709508b691f724b1a498fb71ad4dd8267413f0f846cfc6900803d53acb0c0fb071dbd79414d249eb30f7df6d4a2bec911ac76623ea547a6cac36b2f22fe9d5ce6b8bc592b638e7bf297c7'
message = 'Your balance: 423.15 BTC'
ciphertext = 'a81c3c9d573ac2863a040051fc8abe40123649590f7400ffd392c9080373ac25a629c650968f22533e4f52e570940e7268646f84f7323b4c41bda39fd875b29ad4e5795acb9cc8795f9c4982fffbb989dcdf886390752d5169825573b3d8d13ca2efc2515d0131f2698081b8fe3862a07e06e89c08b2e8725176c6d387f0100ef65a401b6d2c21ff1731e2aea13eb9d8693884a50ffb89373485548a266a5236b429eabfe5bcfbcd7c3ade843c994e7b79e7ba4481e8228fb8e3cdfaac33a58283f52edc62144a735ef2005638cbcecf2b491401476af9f641321ce96ea84788f2bd3dc2f9dc391a4247be32ce25f1d237a0b618b8ab41906de7aff50d42c35f'

def text2Int(text):
    """Convert a text string into an integer"""
    return reduce(lambda x, y : (x << 8) + y, map(ord, text))

def pkcs_encode(message, em_len, ps=None):
    '''Performs EMSA PKCS1 v1.5 padding (for signature)'''
    digest_algo = hashlib.sha256(message)
    digest = digest_algo.digest()
    suffix = b'\x30\x31\x30\x0d\x06\x09\x60\x86\x48\x01\x65\x03\x04\x02\x01\x05\x00\x04\x20' + digest # this depends on the algo
    if em_len < len(suffix) + 11:
        raise Error('Message too short')
    ps_len = em_len - len(suffix) - 3
    ps = b'\xff' * ps_len
    return b'\x00\x01' + ps + b'\x00' + suffix

def egcd(a, b):
    if a == 0:
        return (b, 0, 1)
    else:
        g, y, x = egcd(b % a, a)
        return (g, x - (b // a) * y, y)

def modinv(a, m):
    g, x, y = egcd(a, m)
    if g != 1:
        raise Exception('modular inverse does not exist')
    else:
        return x % m
    
def compute_private_exponent(p, q, e):
    '''Computes the private exponent from primes and public exponent'''
    phi = (p-1)*(q-1)
    d = modinv(e, phi)
    return d
    
# ---------------------- MAIN ----------------------------------
# read the public key
print 'Reading public key'
public_key = RSA.importKey(open(publickeyfile).read())
n = getattr(public_key, 'n')
e = getattr(public_key, 'e')
print 'n: ',n
print 'e: ',e

#sig2 = open(faultysigfile).read() # faulted signature
print 'Message: ',message
print 'Faulty sig: ', faultysig

padded_msg = pkcs_encode(message, 256)
print 'Padded message: ',padded_msg.encode('hex')
print 'Padded message as int: ', text2Int(padded_msg)

# exploit fault on CRT to factorize n
print 'Factorizing n...'
p1 = gcd(pow(text2Int(faultysig.decode('hex')),e) - text2Int(padded_msg), n)
print 'p1: ', p1

p2 = n // p1
print 'p2: ', p2

# computing private key
d = compute_private_exponent(p1, p2, e)
privateKey = RSA.construct((n, e, d))
print 'private exponent: ', d

# Decrypt the ciphertext
print 'ciphertext', ciphertext
decipher_rsa = PKCS1_OAEP.new(privateKey)
plaintext = decipher_rsa.decrypt(ciphertext.decode('hex'))
print plaintext




