from register import logs
from utils import *
from ascii_art import face

source = open("utils.py","r").read()

credentials = {"Wendel" : "AiZAEU7VlRWfJLDUpN9", "Vlastimir" : "1VD6xVSS8PdfhUXL7Q3C", "Freyr" : "uyN4gZ23Im5ZFyTBgj8"}

def print_money():
	for k in known_users:
		print("{:s}: {:d} ph0wncoins".format(known_users[k]['username'],known_users[k]['money']))

def menu():
	global count
	print("\n")
	print_money()
	print("\n")
	print(
		"""
		1 - Python API tutorial
		2 - View register
		3 - Make a transaction
		4 - Buy the holy grail
		5 - Exit
		"""
		)
	choice = input("Choice: ")
	if choice == "1":
		print(source)		
	elif choice == "2":
		print(logs)
	elif choice == "3":
		try:
			source_address = input("Source address: ")
			if source_address not in known_users:
				print("Unknown address.")
				return 0
			destination_address = input("Destination address: ")
			if destination_address not in known_users:
				print("Unknown address.")
				return 0
			amount = int(input("Amount to transfer:"))
			if register_transaction(source_address,destination_address,amount):
				count += 1
		except:
			print("Error. Exiting.")
			exit(1)

	elif choice == "4":
		if known_users[logged_address]['money'] >= 1000000:
			print(face)
			print("ph0wn{GPG_d0es_it_f4st3r_not_s4fer...}")
			exit(0)
		else:
			print("Looks like you are a few bucks off one million...")

	elif choice == "5":
		print("Bye.")
		exit(0)
	else:
		print("Incorrect input.")
		exit(1)


if __name__ == '__main__':
	username = input("username: ")
	if username not in credentials:
		print("Unknown user.")
		exit(1)
	password = input("password: ")
	if credentials[username] == password:
		print("Welcome back {:s}!".format(username))
		print("Here is a small reward : ph0wn{But_my_3LK_t1meser1e_looked_n1ce_:'(}\n")
		logged_user = username
		logged_address = H(username.encode()).hexdigest()
	else:
		print("Invalid password.")
		exit(1)

	count = 0
	while True:
		if count > 5:
			print("There is an unusual peek of transfers right now. Alerting Security services to investigate. Please try again later.")
			exit(1)
		menu()