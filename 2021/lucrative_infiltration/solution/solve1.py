from Crypto.Util.number import *
from tqdm import tqdm

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

def encrypt(pt,seed):
	cipher.set_seed(seed)
	pt += (4 - (len(pt) % 4)) * b"\x00"
	ct = b""
	for i in range(0,len(pt),4):
		ct += xor(cipher.output(),pt[i:i+4])
		cipher.next_state()
	return ct

# {'username': 'Vlastimir', 'timestamp': 1638221826, 'token': '11cd7875ec982aa31fa4124ee207bd3b542098bb78b768f0505ab484', 'proof': '6edb172df503b98194a74813d849b84d2274d6b7b521da1d19ca00818d38f06d'}


# {'username': 'Vlastimir', 'timestamp': 1638221830, 'token': '11cd78715926b16f75a1ceca6eb2467f052ef1a7badecbb40e63ca08', 'proof': '6edb172df503b98194a74813d849b84d2274d6b7b521da1d19ca00818d38f06d'}


# {'username': 'Vlastimir', 'timestamp': 1638221834, 'token': '11cd787d85acd52b43ab7b76993d4ea3f62c2aa3fc66ad782464df8c', 'proof': '6edb172df503b98194a74813d849b84d2274d6b7b521da1d19ca00818d38f06d'}


# {'username': 'Vlastimir', 'timestamp': 1638221841, 'token': '11cd7866495722a23a103151d336121a0c6b42e82ebfda31e7f74533', 'proof': '6edb172df503b98194a74813d849b84d2274d6b7b521da1d19ca00818d38f06d'}

t1 = 1638221826
t2 = 1638221830
t3 = 1638221834
t4 = 1638221841

token1 = bytes.fromhex('11cd7875ec982aa31fa4124ee207bd3b542098bb78b768f0505ab484')
token2 = bytes.fromhex('11cd78715926b16f75a1ceca6eb2467f052ef1a7badecbb40e63ca08')
token3 = bytes.fromhex('11cd787d85acd52b43ab7b76993d4ea3f62c2aa3fc66ad782464df8c')
token4 = bytes.fromhex('11cd7866495722a23a103151d336121a0c6b42e82ebfda31e7f74533')

candidates = []

high_second_1_b = xor(b"n:", token1[4:6])
high_second_1 = bytes_to_long(high_second_1_b)


for low_second_1 in tqdm(range(2 ** 16)):
	low_second_1_b = long_to_bytes(low_second_1,2)
	second_1_b = high_second_1_b + low_second_1_b
	second_4_b = xor(xor(second_1_b,token1[4:8]),token4[4:8])
	second_1 = bytes_to_long(second_1_b)
	second_4 = bytes_to_long(second_4_b)
	a_trial = ((second_4 - second_1) * inverse(t4 - t1,2 ** 32)) % 2 ** 32
	b_trial_1 = (second_1 - a_trial * t1) % 2 ** 32

	second_3_b = xor(xor(second_1_b,token1[4:8]),token3[4:8])
	second_3 = bytes_to_long(second_3_b)
	second_2_b = xor(xor(second_1_b,token1[4:8]),token2[4:8])
	second_2 = bytes_to_long(second_2_b)

	b_trial_2 = (second_2 - a_trial * t2) % 2 ** 32
	b_trial_3 = (second_3 - a_trial * t3) % 2 ** 32

	if b_trial_2 == b_trial_3 == b_trial_1:
		candidates.append((a_trial,b_trial_2))


for a,b in candidates:
	cipher = Cipher(a,b)
	pt1 = encrypt(token1,t1)
	pt2 = encrypt(token2,t2)
	#print(pt1,pt2)
	if pt1[:12] == pt2[:12]:
		print(pt1,a,b)