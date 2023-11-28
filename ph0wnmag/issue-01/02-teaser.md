# Ph0wn 2023 teaser by Jan Degrieck

The teaser was created by *Cryptax*. This writeup is by *Jan Degrieck*. The teaser was solved by ~15+ people.

## Website analysis

### Method number 1: Source code analysis

Notice ph0wn website is hosted on Github:
```
$ curl -s -I https://ph0wn.org | grep server
server: GitHub.com
```
The [git repo](https://github.com/ph0wn/ph0wn.github.io) of the source code of this website is available on [GitHub](https://github.com/ph0wn/ph0wn.github.io).

Looking at the commits around June 2023, we notice the following [interesting commit on May 23](https://github.com/ph0wn/ph0wn.github.io/commit/e204d76371728145c7040f6fe3f1497c3ceff70f): changing the image file size without any visible modification of the image rendering.

### Method number 2: Guessing

We know that the challenge has been added in June 2023. We have a look at archive.org to look at the differences.
We compare with latest snapshot before june : [March 21st, 2023](https://web.archive.org/web/20230321065542/https://ph0wn.org/)

We identify a beautiful new banner image: [https://ph0wn.org/assets/img/ph0wn2023-main.jpg](https://ph0wn.org/assets/img/ph0wn2023-main.jpg)
We guess the challenge is in the image.

## Image analysis

We download the new banner image and analyze it with binwalk. We identify an ELF binary.

```
$ binwalk -z ph0wn2023-main.jpg
                                                                                                                   
DECIMAL       HEXADECIMAL     DESCRIPTION                                                                            
--------------------------------------------------------------------------------                                     
0             0x0             JPEG image data, JFIF standard 1.01                                                                                                                                                                          
30            0x1E            TIFF image data, little-endian offset of first image directory: 8              
316           0x13C           JPEG image data, JFIF standard 1.01                                                    
164304        0x281D0         ELF, 64-bit LSB shared object, version 1 (SYSV)                                        
170873        0x29B79         Unix path: /usr/lib/gcc/aarch64-linux-gnu/10/../../../aarch64-linux-gnu/Scrt1.o
```

We get the size of the file with `ls -al`:
```
$ ls -al ph0wn2023-main.jpg                                                                                        
-rw-r--r-- 1 kali kali 173848 Nov  7 03:06 ph0wn2023-main.jpg
```

We extract the ELF binary with dd. We compute the length to extract (size of the file - offset = 173848 - 164304 = 9544)
```
$ dd if=ph0wn2023-main.jpg of=ph0wn2023-main.bin skip=164304 count=9544 bs=1
9544+0 records in
9544+0 records out
9544 bytes (9.5 kB, 9.3 KiB) copied, 0.00766004 s, 1.2 MB/s
```

## ELF analysis

We notice the ARM64 architecture. ARM architecture is popular on smart devices. This is a nice reference to the theme of the CTF.

```
$ file ph0wn2023-main.bin 
ph0wn2023-main.bin: ELF 64-bit LSB pie executable, ARM aarch64, version 1 (SYSV), dynamically linked, interpreter /lib/ld-linux-aarch64.so.1, BuildID[sha1]=8c3971d6f371f35f21e07be9aea36133b62f3bfc, for GNU/Linux 3.7.0, not stripped
```
### Method number 1: binary analysis

We decompile and disassemble it with Ghidra.

We identify the following two functions:

Main function:

```c
void main(void)

{
  undefined auStack_b0 [168];
  void *local_8;
  
  memcpy(auStack_b0,&DAT_001009e8,0xa8);
  local_8 = (void *)deobfuscate(auStack_b0,0xa8,0x23);
  printf("%s",local_8);
  free(local_8);
  return;
}
```


Deobfuscate function:

```c
void * deobfuscate(long param_1,int param_2,byte param_3)

{
  void *pvVar1;
  int local_4;
  
  pvVar1 = malloc((long)(param_2 + 1));
  *(undefined *)((long)pvVar1 + (long)param_2) = 0;
  for (local_4 = 0; local_4 < param_2; local_4 = local_4 + 1) {
    *(byte *)((long)pvVar1 + (long)local_4) = *(byte *)(param_1 + local_4) ^ param_3;
  }
  return pvVar1;
}
```
This basically xors data blob located at 0x9e8 (2536), of length 0xa8 (168) with 0x23 and prints it.

We extract the blob with dd:

```
$ dd if=ph0wn2023-main.bin of=ph0wn2023-main.data bs=1 skip=2536 count=168
```

We use [CyberChef](https://gchq.github.io/CyberChef/#recipe=XOR(%7B'option':'Hex','string':'23'%7D,'Standard',false)&input=Dg4ODg4Dc2d2Dg4OEgwSAw4ODg4ODikTExISExMTGhsSERIXEBUWGxRlGhMTExMTYRBgYBIUFRYbGmUUFRplFxBiE2ZgYWETZhpiGxRmZWITEBcQZxMXFREaFWYaYhNlYhJgYRcUFWFlZmViEhsVExEbZhsVZ2dnZxJhZxdhZ2dgGhFhFWZmZhIQFxBnZmcQZmEUFRtiZ2ZiERUTFilvRk1EV0sZAxUV)
) to xor the content of the `ph0wn2023-main.data` file with 0x23. 

```
https://gchq.github.io/CyberChef/#recipe=XOR(%7B'option':'Hex',
'string':'23'%7D,'Standard',false)&input=Dg4ODg4Dc2d2Dg4OEgwSAw
4ODg4ODikTExISExMTGhsSERIXEBUWGxRlGhMTExMTYRBgYBIUFRYbGmUUFRplF
xBiE2ZgYWETZhpiGxRmZWITEBcQZxMXFREaFWYaYhNlYhJgYRcUFWFlZmViEhsV
ExEbZhsVZ2dnZxJhZxdhZ2dgGhFhFWZmZhIQFxBnZmcQZmEUFRtiZ2ZiERUTFil
vRk1EV0sZAxUV
```

We get the following:

```
----- PDU---1/1 ------
001100098121436587F900000B3CC176589F769F43A0ECBB0E9A87EFA0343D0
46296E9A0FA1CB476BFEFA186028E86DDDD1BD4BDDC92B6EEE1343DED3EB768
ADEA2605
Length: 66
```

### Method number 2: blindy trust the execution of the binary

Another method however not the good practice is to execute the binary (because we don't know *what* we are executing).
If we have a ARM64 device, then the execution and the display of the result is direct.

Otherwise, it is possible to execute binaries for ARM64 on x86_64 architectures.
Example for Kali (debian based):

```
$ sudo apt update
$ sudo apt install qemu-user qemu-user-static gcc-aarch64-linux-gnu binutils-aarch64-linux-gnu binutils-aarch64-linux-gnu-dbg build-essential
```
Source: [Azeria Labs ARM on x86 QEMU USER](https://azeria-labs.com/arm-on-x86-qemu-user/)

We are then able to execute ARM binaries but a library is missing:

```
$ chmod +x ph0wn2023-main.bin
$ ./ph0wn2023-main.bin
aarch64-binfmt-P: Could not open '/lib/ld-linux-aarch64.so.1': No such file or directory
```

For adding `/lib/ld-linux-aarch64.so.1` required lib:

```
$ sudo dpkg --add-architecture arm64
$ sudo apt update
$ sudo apt install libc6:arm64
```
Source: [https://unix.stackexchange.com/questions/751329/qemu-aarch64-could-not-open-lib-ld-linux-aarch64-so-1-no-such-file-or-direc](https://unix.stackexchange.com/questions/751329/qemu-aarch64-could-not-open-lib-ld-linux-aarch64-so-1-no-such-file-or-direc)

```
$ ./ph0wn2023-main.bin
----- PDU---1/1 ------
001100098121436587F900000B3CC176589F769F43A0ECBB0E9A87EFA0343D0
46296E9A0FA1CB476BFEFA186028E86DDDD1BD4BDDC92B6EEE1343DED3EB768
ADEA2605
Length: 66
```


## PDU analysis

We google to know what PDU is and what could be that content. We find the following site:
[https://www.gsmfavorites.com/documents/sms/pdutext/](https://www.gsmfavorites.com/documents/sms/pdutext/)

Starting with 0011, we can notice this ought to be a SMS-SUBMIT message. SMS based connectivity is quite popular on smart devices. This is a another nice reference to the theme of the CTF.

We use an [online sms pdu decoder](https://www.diafaan.com/sms-tutorials/gsm-modem-tutorial/online-sms-pdu-decoder/)

```
Text message
To: 	

123456789
Message: 	

Amazing! You saw it! Let us know!

ph0wn{we-R-waiting-4-U}
```
\newpage
