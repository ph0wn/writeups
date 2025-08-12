- binwalk on the firmware
- notice there is something at 0x1337 (leet) address

DECIMAL       HEXADECIMAL     DESCRIPTION
--------------------------------------------------------------------------------
4919          0x1337          Squashfs filesystem, little endian, version 4.0, compression:gzip, size: 829726 bytes, 3 inodes, blocksize: 131072 bytes, created: 2021-10-28 10:18:30
2712678       0x296466        eCos RTOS string reference: "ECOS"

- extract with binwalk -e
- in the squashfs there is a file flag.txt.gz
- gunzip and find the flag

