import time
from hashlib import sha256
import random

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
	seed = int(time.time())
	cipher.set_seed(seed)
	pt += (4 - (len(pt) % 4)) * b"\x00"
	ct = b""
	for i in range(0,len(pt),4):
		ct += xor(cipher.output(),pt[i:i+4])
		cipher.next_state()
	return ct, seed

if __name__ == '__main__':

	credentials = {"Wendel" : "AiZAEU7VlRWfJLDUpN9", "Vlastimir" : "1VD6xVSS8PdfhUXL7Q3C", "Freyr" : "uyN4gZ23Im5ZFyTBgj8"}
	usernames = ["Wendel","Vlastimir","Freyr"]
	constants = {"Wendel" : (453241746, 1462504568), "Vlastimir" : (756979985, 1952653779), "Freyr" : (1082754252, 4052067422)}

	while True:
		time.sleep(random.randint(3,5))
		user = random.choice(usernames)
		password = b"ph0wn:" + credentials[user].encode()
		A = constants[user][0]
		B = constants[user][1]
		cipher = Cipher(A,B)
		enc, timestamp = encrypt(password)
		print({"username":user,"timestamp":timestamp,"token":enc.hex(),"proof": sha256(A.to_bytes(4,byteorder="big") + B.to_bytes(4,byteorder="big") + password).hexdigest()})
		print("\n")

