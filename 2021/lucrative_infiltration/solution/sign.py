from hashlib import sha256
from Crypto.Util.number import *
from Crypto.Random.random import randrange

q = 89666094075799358333912553751544914665545515386283824011992558231120286657213785559151513056027280869020616111209289142073255564770995469726364925295894316484503027288982119436576308594740674437582226015660087863550818792499346330713413631956572604302171842281106323020998625124370502577704273068156073608681
p = 2*q + 1
g = 5
H = sha256
threshold = 166

privkey = 197735540781852965006791285509207159254746538034313782393956775286111151694

def fast_rng():
	return randrange(2**(3 * threshold // 2))

def sign_transaction(source_addr, dest_addr, money, privkey):
	m = (source_addr + " -> " + dest_addr + ": " + str(money)).encode()
	k = fast_rng()
	while GCD(k,p-1) != 1:
		k = fast_rng()
	r = pow(g,k,p)
	h = int(H(m).hexdigest(),16)
	s = ((h - privkey * r)* inverse(k,p-1)) % (p-1)
	assert(s != 0)
	return (m,r,s)


print(sign_transaction("006706ff90d78db7250d3f248650f94aae5c28928e9d1cef93f1b70807e6f191","cecbd3a04d51d4e938d6d65b1c6790db6cfc84b5f0855d0362b486f0026da0d0",1000000,privkey))