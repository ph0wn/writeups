# Pacman

This is a MAME ROM. Install MAME `apt-get install mame-tools mame` to play or DAME (Dockerized MAME). The zip of the ROM must be put in the `roms` directory.

- 5 for coins
- 1 to start player 1

## Comparing the ROM

I download [puckmanb.zip](https://edgeemu.net/details-20298.htm)

Unchanged:

- 82s123.7f
- 82s126.1m: sound ROM
- 82s126.3m: other sound ROM
- 82s126.4a
- namcopac.6e
- namcopac.6h
- pacman.5e
- pacman.5f: sprite ROM (see [here](http://www.lomont.org/Software/Games/PacMan/PacmanEmulation.pdf))

Changed:

- namcopac.6f: quite a lot of changes - the size is not changed
- namcopac.6j: quite a lot of changes - the size is not changed

```
$ cmp -l namcopac.6f ../puckmanb/namcopac.6f | gawk '{printf "%08X %02X %02X\n", $1, strtonum(0$2), strtonum(0$3)}' | head 
000009EC 06 C3
000009ED 40 9A
000009EE 70 1F
00000F9B 00 E5
00000F9C 00 D5
00000F9D 00 F5
00000F9E 00 11
00000F9F 00 80
00000FA0 00 FC
00000FA1 00 19

$ cmp -l namcopac.6j ../puckmanb/namcopac.6j | gawk '{printf "%08X %02X %02X\n", $1, strtonum(0$2), strtonum(0$3)}' | head
0000001B 20 3E
0000001C 15 00
00000032 25 B4
00000033 7C 76
00000034 E6 99
00000035 F0 FE
00000CE1 00 01
00000CE2 00 04
00000CE3 00 02
00000CE4 00 05

## Disassembling namcopac.6f

Use radare2: `r2 -e asm.arch=z80 namcopac.6f`

The first modifications occur at 0x9ec - 0x9ee.
In this original ROM, this corresponds to:

```
0x000009eb      0640         ld b, 0x40
0x000009ed      70             ld [hl], b    ; load 0x40 in the RAM
```


