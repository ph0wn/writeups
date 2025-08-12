Katy is so happy. She has finished her first challenge based on
Windows 10 IoT Core. The .NET world is so wonderful. You can create a
library that is running on a Raspberry Pi, or your x64 desktop
computer. No need to recompile, it just works. It was not an easy
challenge to create, with many unexpected problems. But it is ready
now. But... wait a minute. Oh no! What a mistake!

On the desk of the organisers, there is a Raspberry Pi 3B running
Windows 10 IoT Core and a custom application, DoubleRainbow. The flag
is given when you press the buttons is the right order. We provide
also a disk image of the Data partition of the microSD card on USB
keys. Find the code using this image and validate your finding on the
actual hardware to get the flag.

Notes: The USB keys contain both Level 1 and Level 2. This challenge
can be solved using any operating system, you do not need to have
Windows. Collect a USB disk at the organizer's desk (and bring it back).

Attachements:
- The schema of the hardware used by the challenge
- On USB key (or on our FTP site `10.210.17.66` id: `ph0wn` pwd: `ph0wn2019`)

```
$ sha1sum double-rainbow-level1.img.bz2
76b5a9826f4451db8a8a8ac6ed9fc2a978075e68  ./usb/double-rainbow-level1.img.bz2

$ sha1sum double-rainbow-level1.img
099ce78d412fba95c82cc8ff135e7a98eebd4da6 double-rainbow-level1.img
```


