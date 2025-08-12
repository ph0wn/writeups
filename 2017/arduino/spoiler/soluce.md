# Solution

## Stage 1

We dump code from the Arduino, using `avrdude`. This program can be found in the arduino package in `ARDUINO_INSTALL_PATH/hardware/tools/avr/bin`.

`avrdude -p partno -P port -c programmer -C configfile -U memtype:op:filename:format`

- partno: what type of MCU is connected to the programmer. [We have an Arduino Uno R3](https://store.arduino.cc/arduino-uno-rev3). This is using the microcontroller ATmega328p.  So part number must be `m328p` (see [avrdude options](http://www.nongnu.org/avrdude/user-manual/avrdude_4.html#Option-Descriptions)).

- programmer: `arduino` in our case

- config file: to be found in `ARDUINO_INSTALL_PATH/hardware/tools/avr/etc/avrdude.conf`

- port: `/dev/ttyACM0` in my case

- memory operation: we will read the flash of the device so memtype is `flash`. The operatio is read: `r`. Then we specify the filename to dump to. Finally the format can be for example `i` for Intel Hex.

Note: there are path issues if you invoke `avrdude` outside `ARDUINO_INSTALL_PATH/hardware/tools/avr/bin`. So, go to that directory first.

### Dumping in raw (easier)

```bash
$ cd ~/softs/arduino-1.8.5/hardware/tools/avr/bin
$ ./avrdude -p m328p -P /dev/ttyACM0 -c arduino -C ../etc/avrdude.conf -U flash:r:flash.raw:r
...
Reading | ################################################## | 100% 4.20s

avrdude: writing output file "challenge.raw"

avrdude: safemode: hfuse reads as 0
avrdude: safemode: efuse reads as 0
avrdude: safemode: Fuses OK (E:00, H:00, L:00)

avrdude done.  Thank you.
```
(you might need to sudo)

Then, do:

```bash
$ strings flash.raw 
!P1	
OB.Q,a,q,
#+$+%+a
O__O
O__O
Stage1 flag: ph0wn{WeHopeYouLikeAVR}
ph0wnArduino
Ph0wn Arduino challenge v1.2
Attempting to connect to WPA2 wifi with stage1 pass...
[-] Attempting to connect with stage2 pass...
[-] Not connected to wifi
[+] Connected to wifi with stage 2 pass
[+] Connected to wifi with stage 1 pass
$N__O
```

So, the first flag is `ph0wn{WeHopeYouLikeAVR}`


### Dumping in Intel Hex mode (longer)

```bash
$ cd ~/softs/arduino-1.8.5/hardware/tools/avr/bin
$ ./avrdude -p m328p -P /dev/ttyACM0 -v -c arduino -C ../etc/avrdude.conf -U flash:r:challenge.ihex:i

avrdude: Version 6.2
         Copyright (c) 2000-2005 Brian Dean, http://www.bdmicro.com/
         Copyright (c) 2007-2014 Joerg Wunsch

         System wide configuration file is "../etc/avrdude.conf"
         User configuration file is "/home/axelle/.avrduderc"
         User configuration file does not exist or is not a regular file, skipping

         Using Port                    : /dev/ttyACM0
         Using Programmer              : arduino
         AVR Part                      : ATmega328P
         Chip Erase delay              : 9000 us
         PAGEL                         : PD7
         BS2                           : PC2
         RESET disposition             : dedicated
         RETRY pulse                   : SCK
         serial program mode           : yes
         parallel program mode         : yes
         Timeout                       : 200
         StabDelay                     : 100
         CmdexeDelay                   : 25
         SyncLoops                     : 32
         ByteDelay                     : 0
         PollIndex                     : 3
         PollValue                     : 0x53
         Memory Detail                 :

                                  Block Poll               Page                       Polled
           Memory Type Mode Delay Size  Indx Paged  Size   Size #Pages MinW  MaxW   ReadBack
           ----------- ---- ----- ----- ---- ------ ------ ---- ------ ----- ----- ---------
           eeprom        65    20     4    0 no       1024    4      0  3600  3600 0xff 0xff
           flash         65     6   128    0 yes     32768  128    256  4500  4500 0xff 0xff
           lfuse          0     0     0    0 no          1    0      0  4500  4500 0x00 0x00
           hfuse          0     0     0    0 no          1    0      0  4500  4500 0x00 0x00
           efuse          0     0     0    0 no          1    0      0  4500  4500 0x00 0x00
           lock           0     0     0    0 no          1    0      0  4500  4500 0x00 0x00
           calibration    0     0     0    0 no          1    0      0     0     0 0x00 0x00
           signature      0     0     0    0 no          3    0      0     0     0 0x00 0x00

         Programmer Type : Arduino
         Description     : Arduino
         Hardware Version: 3
         Firmware Version: 4.4
         Vtarget         : 0.3 V
         Varef           : 0.3 V
         Oscillator      : 28.800 kHz
         SCK period      : 3.3 us

avrdude: AVR device initialized and ready to accept instructions

Reading | ################################################## | 100% 0.00s

avrdude: Device signature = 0x1e950f (probably m328p)
avrdude: safemode: hfuse reads as 0
avrdude: safemode: efuse reads as 0
avrdude: reading flash memory:

Reading | ################################################## | 100% 4.20s

avrdude: writing output file "challenge.ihex"

avrdude: safemode: hfuse reads as 0
avrdude: safemode: efuse reads as 0
avrdude: safemode: Fuses OK (E:00, H:00, L:00)

avrdude done.  Thank you.
```

Then, you need to convert the file to binary format. For that, there is an Intel hex python package: `sudo pip install bincopy`.

Then, convert with this small script:

```python
import bincopy
import sys

f = bincopy.BinFile()
f.add_ihex_file(sys.argv[1])
print(f.as_binary())
```

## Stage 2

We need to reverse the dumped AVR program: `r2 -a avr -b 8 flash.raw`

```
[0x000000c4]> e asm.arch=avr
[0x000000c4]> aaa
[0x000000c4]> ie
[Entrypoints]
vaddr=0x000000c4 paddr=0x000000c4 baddr=0xffffffffffffffff laddr=0x00000000 haddr=-1 type=program
...
[0x000000c4]> izz~flag
vaddr=0x00000e8a paddr=0x00000e8a ordinal=008 sz=37 len=36 section=unknown type=ascii string=Stage1 flag: ph0wn{WeHopeYouLikeAVR}
vaddr=0x00000f2b paddr=0x00000f2b ordinal=012 sz=30 len=29 section=unknown type=ascii string=Stage 2: find the other flag!
...
[0x000000c4]> e asm.describe = true
...
[0x000000c4]> afl
0x000000c4   12 3524 -> 84   entry0
```

Now let's have a look at `entry0`.

```
[0x000000c4]>pd 40
...
0x00000102      0e943807       call fcn.00000e70           ; long call to a subroutine ; fcn.00000e70
...
0x0000010a      c9f7           brne 0xfe                   ; branch if not equal ; unlikely
0x0000010c      0e941906       call fcn.00000c32           ; long call to a subroutine ; fcn.00000c32
```

`fcn.00000e70` is not interesting. `fcn.00000c32` is very interesting :)





TO DO.


The flag is `ph0wn{8c14d876907759e705b9f0e0eacfce1e}`

# References

A. Cervoise, [Hands-on security for DIY projects](https://sec2016.rmll.info/files/20160706-02-Cervoise-Security-for-DIY-projects.pdf)
