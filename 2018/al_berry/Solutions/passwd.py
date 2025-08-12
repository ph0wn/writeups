from socket import socket
from telnetlib import Telnet

sock = socket()
sock.connect(('192.168.0.100', 12345))
print sock.recv(1024)
sock.send("ph0wn{RopRopRop}\x0A")
#interactive mode
t = Telnet()
t.sock = sock
t.interact()
sock.close()

