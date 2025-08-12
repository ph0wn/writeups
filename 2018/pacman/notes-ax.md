go to softs/DAME
put the zips in roms
./build.sh
./start.sh
go to available games
select puckman
play
5 for coins
1 player 1

apt-get install mame-tools mame

/usr/bin/romcmp
http://www.inkland.org.uk/dz80/

# Comparing the ROM

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
```

## Disassembling Z80 6f

https://www.altsci.com/old_non-x86/

`r2 -e asm.arch=z80 namcopac.6f`

### Bytes 9ec - 9ee

000009EC 06 C3
000009ED 40 9A
000009EE 70 1F

#### Original bytes

ORIGINAL:
- offset -   0 1  2 3  4 5  6 7  8 9  A B  C D  E F  0123456789ABCDEF
0x000009ea  3f06 4070 0619 4fcb 39cd                 ?.@p..O.9.

This corresponds to:

```
0x000009eb      0640           ld b, 0x40
0x000009ed      70             ld [hl], b
```

We are loading 0x40 in the RAM.

```
           0x000009cd      3eff           ld a, 0xff            ; a = 0xff
            0x000009cf      329d4d         ld [0x4d9d], a       ; put 0xff at the address of 0x4d9d (game variables RAM)
            0x000009d2      2a394d         ld hl, [0x4d39]     ; hl = *(0x4d39)
            0x000009d5      cd6500         call 0x0065      ; this does just a ret ?
            0x000009d8      7e             ld a, [hl] 	        ; a = *(0x4d39)
            0x000009d9      fe10           cp 0x10              ; if a == 0x10 --> character "."
        ,=< 0x000009db      2803           jr z, 0x03        ; then jump to 0x9e0
        |   0x000009dd      fe14           cp 0x14             ; if a == 0x14 --> big dot
        |   0x000009df      c0             ret nz                 ; return
        `-> 0x000009e0      dd210e4e       ld ix, 0x4e0e ; load counter
            0x000009e4      dd3400         inc [ix+0x00] ; increment counter 0x4e0e
            0x000009e7      e60f           and 0x0f        ; take lowest nibble 
            0x000009e9      cb3f           srl a                ; / 2
            0x000009eb      0640           ld b, 0x40
            0x000009ed      70             ld [hl], b         ; load b in hl which is *(0x4d39)
```

- put 0xff at the address of 0x4d9d
- load 0x4d39 pointer in RAM
- if character is dot, then increment counter 0x4e0e and put an empty space at 0x4d39

So:
- 0x4d39 is a pointer to the current position of the pacman

- [RAM map](http://ece545.com/F16/reports/F15_Pacman.pdf). This map explicits the 'RAM' part.

#### Patched bytes

MODIFIED:
[0x000009ea]> px 10
- offset -   0 1  2 3  4 5  6 7  8 9  A B  C D  E F  0123456789ABCDEF
0x000009ea  3fc3 9a1f 0619 4fcb 39cd                 ?.....O.9.

We see that c3 9a 1f correspond to jp to 0x1f9a
0x000009eb      c39a1f         jp 0x1f9a

This is a **jump to 0xf9a**.
The patch is located between 0xf9a and 0xfc7.



### Reversing the patch

If we disassemble in 0xf9a, we have:

```
[0x000009ea]> s 0xf9a
[0x00000f9a]> pd 30
           0x00000f9a      e5             push hl                     ; push data on the stack hl stores the current position of the pacman
            0x00000f9b      d5             push de
            0x00000f9c      f5             push af
            0x00000f9d      1180fc         ld de, 0xfc80
            0x00000fa0      19             add hl, de                  ; move hl to current pos + 0xfc80
            0x00000fa1      3a134e         ld a, [0x4e13]              ; [0x4e13:1]=255 ; is 0x4e13 the pacman level
            0x00000fa4      5f             ld e, a                     ; for first level, a will be 0, so will e be
            0x00000fa5      1600           ld d, 0x00
            0x00000fa7      19             add hl, de
            0x00000fa8      7d             ld a, l
            0x00000fa9      d6e0           sub 0xe0                    ; substract 0x3fe0
            0x00000fab      7c             ld a, h
            0x00000fac      de3f           sbc a, 0x3f
        ,=< 0x00000fae      3011           jr nc, 0x11                 ; check we are not beyond limits
        |   0x00000fb0      7e             ld a, [hl]                  ; load value at current position in a
        |   0x00000fb1      ab             xor e                       ; for first level XOR 0 won't do anything
        |   0x00000fb2      e607           and 0x07
       ,==< 0x00000fb4      200b           jr nz, 0x0b                 ; jump if not equal to 0
       ||   0x00000fb6      7e             ld a, [hl]
       ||   0x00000fb7      cb3f           srl a
       ||   0x00000fb9      cb3f           srl a
       ||   0x00000fbb      cb3f           srl a                       ; divide by 8
       ||   0x00000fbd      c640           add a, 0x40                 ; we write letters only in the patch and they are between 0x41 and 0x5a
       ||   0x00000fbf      47             ld b, a                     ; b=a=character to write
       ||   0x00000fc0      210640         ld hl, 0x4006               ; beware we actually jump in 0xfc1 from 0xfb4
            0x00000fc3      f1             pop af
            0x00000fc4      d1             pop de
            0x00000fc5      e1             pop hl
            0x00000fc6      70             ld [hl], b                  ; write character at position
        ,=< 0x00000fc7      c3ee19         jp 0x19ee
```

In 0xfb4, the jump actually goes to 0xfb6+0x0b = 0xfc1
```
[0x00000fc1]> pd 3
            0x00000fc1      0640           ld b, 0x40
            0x00000fc3      f1             pop af
            0x00000fc4      d1             pop de
```

- af, de and hl are double registers [see here](http://quasar.cpcscene.net/doku.php?id=iassem:z80init)
- data are stored in db or dw
- this is a routine. We have push hl, de, af. The pops are in 0xfc3.

```
0x00000fc3      f1             pop af        
0x00000fc4      d1             pop de      
0x00000fc5      e1             pop hl 
```
and just after that we jump to 0x19ee

```
0x00000fc6      70             ld [hl], b                  ; Load accumulator, register or location . 
0x00000fc7      c3ee19         jp 0x19ee
```

This is a jump just back to where we were. So the **patch is very obviously between 0xf9a and 0xfc7**


- first 3: push. That's for the routine. Not interesting.
- ld de and add hl, de: save some space on the stack?
- load content of 0x4e13 in a
- we have a jump to the end of the routine if there is an overflow?
- we take part of a memory zone and do XOR with value in RAM, then 0x07.
- jump to the end if ...
- put hl in a, and then divide it by 8. Finally add 0x40. Put that in b. This will be the return value.

- ld hl, 0x4006: this load the video RAM to hl.

Information:

- [Z80](https://en.wikipedia.org/wiki/Zilog_Z80)
- Z80 has a 16 bit memory, so all addresses lie within 0x0000-0xffff.
- [Memory map](http://www.lomont.org/Software/Games/PacMan/PacmanEmulation.pdf) or [here](http://www.euro-arcade.de/files/pacman_mm/pacman_mm.htm)

The first four K are for the 4 ROMs. If we go to 0x4e13, that should go the **RAM**.


## 6j

This ROM will be loaded in 0x3000-0x3fff. (see [here](http://www.lomont.org/Software/Games/PacMan/PacmanEmulation.pdf)).

The first few bytes of this ROM are a checksum check.

The ROM is patched:

```
0000001B 20 3E
0000001C 15 00
```

and replaces the expected "jump if checksum fail" by an instruction that does not do much:

`0x0000001a      3e00           ld a, 0x00`

Apparently, we have a WatchdogReset in **0x50c0**.
And the CoinCounter is **0x5007**

There are also some modifications here:

```
00000032 25 B4
00000033 7C 76
00000034 E6 99
00000035 F0 FE
```

which correspond to a halt (probably if the checksum fails):

```
0x00000032      76             halt                        ; halt computer and wait for interrupt. 
0x00000033      99             sbc a, c                    ; a,s or HL,ss Subtract operands from accumulator with carry. 
0x00000034      fe32           cp 0x32
```

The other modifications are from **0xce1 - 0x1000**
We don't have code there. It's data:

```
[0x00000ce1]> px 60
- offset -   0 1  2 3  4 5  6 7  8 9  A B  C D  E F  0123456789ABCDEF
0x00000ce1  0402 0501 0303 0201 0302 0506 0406 0003  ................
0x00000cf1  0102 0203 0005 0202 0505 0601 0505 0003  ................
0x00000d01  052c 0302 0100 0202 0104 0500 0603 0602  .,..............
0x00000d11  0201 0305 0001 0102 0404 0103            ............
```

This looks more like video tiles.
We have a tile RAM (1K) and a palette RAM (1K).
The layout is explained in [Figure 7](http://www.lomont.org/Software/Games/PacMan/PacmanEmulation.pdf). For example 0x4000 would be right nearly bottom row.

## Decoding

### First level

Let's say we want to decode character at position 0x42b8.
0x42b8+ 0xfc80 = 0x42b8 - 0x380 = 0x3f38

So in namcopac.6j:

```
[0x00000f38]> px 20
- offset -   0 1  2 3  4 5  6 7  8 9  A B  C D  E F  0123456789ABCDEF
0x00000f38  2841 2a83 4475 2e47 0504 0602 0603 3829  (A*.Du.G......8)
0x00000f48  0001 0585   
```

28 ^ 0x00 & 0x07 = 0
So we do not jump
0x28 >> 3 = 5
So we will load character 0x45 = E

Next character is in 0x4298: so we go to 0x3f18
We have 0x98. So we do not jump, 0x98 >> 3 = 0x13, so we load 0x53=S

We are going to have ESAME.

## Level 2

assume we have 0x4e13 = 1
0xfc80 + 0x42b8 -->
de = 0x00 + 0x01
0xfc80 + 0x42b8 + 1

0x41 ^ 0x01 & 0x07  = 0
0x41 >> 3 = 8 --> H

0x79 ^ 0x01 & 0x07
--> O




# References

http://z80.info/zip/z80cpu_um.pdf
- [disassembling sound in pacman](http://www.vecoven.com/elec/pacman/code/pac_z80.html)
