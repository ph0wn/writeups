from time import time
from hashlib import sha256

class Cipher:
	def __init__(self,a,b):
		self.a = a
		self.b = b
		self.m = 2 ** 32

	def next_state(self):
		self.state = (self.a * self.state + self.b) % self.m

	def set_seed(self,seed):
		self.state = seed

	def output(self):
		return self.state.to_bytes(4,byteorder="big")


def xor(a,b):
	return bytes(x^y for x,y in zip(a,b))

def encrypt(pt):
	seed = int(time())
	cipher.set_seed(seed)
	pt += (4 - (len(pt) % 4)) * b"\x00"
	ct = b""
	for i in range(0,len(pt),4):
		ct += xor(cipher.output(),pt[i:i+4])
		cipher.next_state()
	return ct, seed


A = int(input("A:")) % 2 ** 32
B = int(input("B:")) % 2 ** 32
assert 2 <= A < 2 ** 32
assert 2 <= B < 2 ** 32

cipher = Cipher(A,B)

username = input("username: ")
password = b"ph0wn:" + input("password: ").encode()
enc, timestamp = encrypt(password)

print("auth_token:",{"username":username,"timestamp":timestamp,"token":enc.hex(),"proof": sha256(A.to_bytes(4,byteorder="big") + B.to_bytes(4,byteorder="big") + password).hexdigest()})