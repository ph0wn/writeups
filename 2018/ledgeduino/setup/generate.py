#! /usr/bin/env python

from Crypto.PublicKey import RSA
from Crypto.Hash import SHA256
from Crypto.Cipher import PKCS1_OAEP
from Crypto.Signature import PKCS1_v1_5
from fractions import gcd

def text2Int(text):
    """Convert a text string into an integer"""
    return reduce(lambda x, y : (x << 8) + y, map(ord, text))
 
def int2Text(number, size):
    """Convert an integer into a text string"""
    text = "".join([chr((number >> j) & 0xff)
                    for j in reversed(range(0, size << 3, 8))])
    return text.lstrip("\x00")

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

# MAIN
RSAkey = RSA.generate(2048)
n = getattr(RSAkey.key, 'n')
p = getattr(RSAkey.key, 'p')
q = getattr(RSAkey.key, 'q')
d = getattr(RSAkey.key, 'd')
e = getattr(RSAkey.key, 'e')
publickey = RSAkey.publickey()
print 'Public key: \n', publickey.exportKey()
file_out = open("rsa_key.pub", "wb")
file_out.write(publickey.exportKey())
file_out.close()

# let's encrypt the flag
flag = 'Congratulations! The flag is ph0wn{cryptax_l1kes_crypto}'
cipher_rsa = PKCS1_OAEP.new(publickey)
ciphertext = cipher_rsa.encrypt(flag)
print 'Encrypted flag: ',ciphertext.encode('hex')
#file_out = open("encrypted", "wb")
#file_out.write(ciphertext)
#file_out.close()

# sign a message
message = 'Your balance: 423.15 BTC'
digest = SHA256.new()
digest.update(message)
signer = PKCS1_v1_5.new(RSAkey)
signature = signer.sign(digest)
#print 'Good signature: ', text2Int(signature)
#file_out = open("good_signature", "wb")
#file_out.write(signature)
#file_out.close()

padded_msg = publickey.encrypt(signature,'')[0]
#file_out = open('padded_msg', 'wb')
#file_out.write(padded_msg)
#file_out.close()

# CRT pre-computations
dp = d % (p-1)
dq = d % (q-1)
qinv = modinv(q,p)

# FAULT that changes one bit
dp = dp - 1
s1 = pow(text2Int(padded_msg), dp, p)
s2 = pow(text2Int(padded_msg), dq, q)
qinv = modinv(q, p)
h = (qinv * (s1 - s2)) % p
sig2 = s2 + h * q # s2 is an integer
#file_out = open('faulty_signature.sig', 'wb')
#file_out.write(int2Text(sig2, 2048))
#file_out.close()
print 'Faulty signature: ', int2Text(sig2, 2048).encode('hex')

'''
Public key: 
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA3g8RQ9p2sA5AQIdG9vk/
Ke5JsFhJa+Kd7brE2FO59n6PC7WPWS0zhA+INvChao2p9AsZ/RYTTlyqGfCY4MCY
TygA9j55gzl0FasGU3HJRtPefAr207INTDVRjOwVTmpXHjHP9itTKsWrMTze6yfI
YY/fxpdxWRHjf6Od8SphGGz3ZtwQulGS32uDXM+sy9fpmnLqaa7rnFRAymKKy9bL
IiFJomlMvqZFmNy+zn1Z2uTRZlxshXMLE/T3Glucp/ySbRgdscRZYrskiY1SJspf
MesS7SF6zYQ8CSPVQfFbipu7CGErZbxlPH9DfLtUpnC2HSGEdfXS0ZRaUqNyX1cV
NQIDAQAB
-----END PUBLIC KEY-----
Encrypted flag:  a81c3c9d573ac2863a040051fc8abe40123649590f7400ffd392c9080373ac25a629c650968f22533e4f52e570940e7268646f84f7323b4c41bda39fd875b29ad4e5795acb9cc8795f9c4982fffbb989dcdf886390752d5169825573b3d8d13ca2efc2515d0131f2698081b8fe3862a07e06e89c08b2e8725176c6d387f0100ef65a401b6d2c21ff1731e2aea13eb9d8693884a50ffb89373485548a266a5236b429eabfe5bcfbcd7c3ade843c994e7b79e7ba4481e8228fb8e3cdfaac33a58283f52edc62144a735ef2005638cbcecf2b491401476af9f641321ce96ea84788f2bd3dc2f9dc391a4247be32ce25f1d237a0b618b8ab41906de7aff50d42c35f
Faulty signature:  11e8b022452ab62d73f486f4e791acbc5935f8b3fa93f5662c1371d40201b7aaade2269c6de72ca7fb5f19fe0f1c7126df0224838c1e228f71798032f246cfb22e3926c5b1682c5f066f79cc6f17e998cefdfc24e5ac8b4201965af18d3932065c52e94129c2d2e924f65c2a66e22644e0c35a24abae004efd45e705ca7b049a6ccb46cefdce6fd825b0339b5d7c883a3690349301c112400eeb27bce7932e56147c3a0c1ed19b2c65f0dcdb7f135c98c98129146ec709508b691f724b1a498fb71ad4dd8267413f0f846cfc6900803d53acb0c0fb071dbd79414d249eb30f7df6d4a2bec911ac76623ea547a6cac36b2f22fe9d5ce6b8bc592b638e7bf297c7
'''

