Reolink Argus Eco
BIPC_36S3216MGUN

I downloaded another one:
RLC-410W
https://reolink.com/download-center/

mksquashfs ./squashfs-root/ fs.squash -comp xz


>>> [hex(ord(i)) for i in list('ph0wn') ]
['0x70', '0x68', '0x30', '0x77', '0x6e']
>>> [hex(ord(i)) for i in list('flag') ]
['0x66', '0x6c', '0x61', '0x67']

dd if=./fs.squash of=./firmware/patched.firmware skip=4919 conv=notrunc bs=1
