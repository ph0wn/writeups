# Don't panic

## Z-Machine

The mention to SuperH is a false positive. This is an Infocom game, to be run on the virtual machine [Z-machine](https://en.wikipedia.org/wiki/Z-machine).

```bash
$ file ./distribute/DONTPANIC 
./distribute/DONTPANIC: Hitachi SH big-endian COFF executable, not stripped
```

I installed the [Frotz interpreter for Z-machine](https://github.com/DavidGriffith/frotz): `sudo apt-get install frotz`

## Using Frotz

Run: `frotz infocom.dat`

![](./screenshots/frotz.png)

[HOW TO PLAY](https://github.com/DavidGriffith/frotz/blob/master/HOW_TO_PLAY)

- `save` / `restore`
- `drop all the tools`
- diagnose
- inventory

```
>version

THE HITCHHIKER'S GUIDE TO THE GALAXY
Infocom interactive fiction - a science fiction story
Copyright (c) 1984 by Infocom, Inc. All rights reserved.
Release 31 / Serial number 871119 / Interpreter 1 Version F
```

## Playing the game

The game is called _THE HITCHHIKER'S GUIDE TO THE GALAXY_.

A [walkthrough can be found here](https://www.gamefaqs.com/pc/564459-the-hitchhikers-guide-to-the-galaxy-1984/faqs/1612).
A better [walkthrough here](http://www.thecomputershow.com/computershow/walkthroughs/hitchhikersguidewalk.htm)

There are three major steps:

1. In your house, you need to get your gown and swallow the analgesic to stop your head spinning:

```
stand up
light
take gown
wear gown
open pocket
take analgesic
take all tools
go south
take mail
```

2. When you get out of your house, a bulldozer wants to run down your house. You need to lie down and wait there until you are made to stand up.

3. Follow Ford to a pub, where you will need to drink a given amount of beer, and then go outside when your house crashes and grab the device Ford drops. Push the green button.

4. In space, you need to wait and smell, hear. You will finally see Ford giving you peanuts. Then, when you activate the machine's switch it will tell you have to listen to captain's poetry and memorize a given word of the second verse.

```
A recording plays: "To open the case, type in the third word from the second verse of the Captain's current favourite poem. WARNING: An
incorrect input will cause the case to explode."
```

Note that the word you are asked is different each time and that the poetry changes a bit too.

To get to the second verse, you'll need to `enjoy poetry` for the Captain to read the second verse.

```
Oh freddled gruntbuggly, thy nacturations are to me!
As plurdled gabbleblotchits on a lurgid bee.
Groop I implore thee, my foonting turlingdromes.
...
Fripping lyshus wimbgunts, awhilst moongrovenly kormzibs.
Bleem miserable venchit! Bleem forever mestinglish asunder frapt.
Gerond withoutitude form into formless bloit, why not then? Moose.
```

5. When you get  back to your room after you have been read the poetry, type the given word on the keyboard:

```
>type "thou" on keyboard
The glass case opens. Also, the Matrix of the Universe is revealed to you for a fraction of a second. You see the Z-machine that runs it
all, including an hexdump of a Ph0wn flag:       0a85 187b 07c1 28ac 1545 987d
```

You don't need to continue the game passed that point.


## Hacking Z-Machine

[Specifications of Z-Machine](http://inform-fiction.org/zmachine/standards/z1point1/index.html)
[Perl ZMachine::ZSCII](https://metacpan.org/pod/ZMachine::ZSCII)
[Step by step explanations on Z-characters and Z-strings](https://rjbs.manxome.org/rubric/entry/2014) - though not totally clear to me ;-)

### ZVM

```python
from zvm import zstring, zmemory
storydata = file("../infocom.dat").read()
zmem = zmemory.ZMemory(storydata)
zmem.print_map()
```

prints:
```
Dynamic memory:  0 - 15155
Static memory:  15156 - 65535
High memory:  28281 - 158413
```

### Ztools

[ztools](http://inform-fiction.org/zmachine/ztools.html).
They built with `make` no problem directly on my host.

Then, you can run:

```bash
$ ./infodump ./distribute/DONTPANIC

Story file is ./distribute/DONTPANIC

    **** Story file header ****

Z-code version:           5
Interpreter flags:        None
Release number:           31
Size of resident memory:  6e79
Start PC:                 7101
Dictionary address:       4a9f
Object table address:     042c
Global variables address: 02c0
Size of dynamic memory:   3b34
Game flags:               None
Serial number:            871119
Abbreviations address:    0200
File size:                26acc
Checksum:                 410d
```

Note that although we can decode strings for the story, they do not include the hexdump we got from the story:

```bash
$ ./txd -S 19103 ./distribute/DONTPANIC | grep -i -C 3 flag
S11562: "MAGRATHEA"
S11563: " opens. Also, the Matrix of the Universe is revealed to you for a
fraction of a second. You see the Z-machine that runs it all, including an
hexdump of a Ph0wn flag:       "
S11564: "21,914"
S11565: "fim"
S11566: "Put something on the satchel, then push the button again."
```

### Solving the hexdump

From the story, I have the hexdump: `0a85 187b 07c1 28ac 1545 987d`
I decode this using the specs.
In Z-Machine, text is made of 2-byte words, so:
- 0a 85
- 18 7b
- 07 c1
- 28 ac
- 15 45
- 98 7d

Each word is divided into three 5-bit 'Z-characters', plus 1 bit left over which marks the end if it is set:

- 0 00010 10100 00101: 0x02 0x14 0x05
- 0 00110 00011 11011: 0x06 0x03 0x1b
- 0 00001 11110 00001 0x01 0x1e 0x01
- 0 01010 00101 01100: 0x0a 0x05 0x0c
- 0 00101 01010 00101: 0x05 0x0a 0x05
- 1 00110 00011 11101: 0x06 0x03 0x1d

So, we have the following flow of Z-characters:
0x02 0x14 0x05 0x06 0x03 0x1b 0x01 0x1e 0x01 0x0a 0x05 0x0c 0x05 0x0a 0x05 0x06 0x03 0x1d

Let's begin. 0x02.

The specs say : "In Versions 3 and later, Z-characters 1, 2 and 3 represent abbreviations". We are in version 5, so the first 0x02 means we are using an abbreviation.

Read forward the specs: " the next Z-character indicates which abbreviation string to print. If z is the first Z-character (1, 2 or 3) and x the subsequent one, then the interpreter must look up entry 32(z-1)+x in the abbreviations table and print the string at that word address."

So, we have z=2 and x=0x14. Meaning we will need to look up for abbrev[32(2-1)+20]=abbrev[52]

We lookup the abbreviation table using `./infodump -a ../infocom.dat`:

```
Story file is ../infocom.dat

    **** Abbreviations ****

[ 0] "the "
[ 1] "you "
[ 2] ", "
[ 3] "You "
...
[51] "nothing "
[52] "Ph0wn "
```

So, the first word is `Ph0wn `.

Now, we have **0x05**.
The specs say : "Z-characters 4 and 5 permanently change alphabet, according to the same table, and are called 'shift lock' characters. "
We were using alphabet A0, we will now use alphabet A2 (punctuation).

Then, we have **0x06**.
The specs say: "Z-character 6 from A2 means that the two subsequent Z-characters specify a ten-bit ZSCII character code: the next Z-character gives the top 5 bits and the one after the bottom 5. "

So, we must now read **0x03 0x1b**.
Top 5 bits: 00011
Bottom 5 bits: 11011
10-bit ZSCII character: 00 0111 1011 -> 7b

See [3.8.3](http://inform-fiction.org/zmachine/standards/z1point1/sect03.html) 0x7b is `{`.

Then, we have 0x01: another abbreviation.
abbrev[32(1-1)+0x1e]=abbrev[30]="Improbability "

Then, 0x01 0x0a: abbrev[32(1-1)+10]="of "

Then, we have 0x05 so we are going to change alphabet again.

See [3.2.3](http://inform-fiction.org/zmachine/standards/z1point1/sect03.html), which says : "In Versions 3 and later, the current alphabet is always A0 unless changed for 1 character only: Z-characters 4 and 5 are shift characters. Thus 4 means "the next character is in A1" and 5 means "the next is in A2". There are no shift lock characters. "
So, 0x05 means the next character is in A2.

We now read **0x0c**. In A2, this is : 4

Then, again 0x05. So, we are again in A2. And next character is **0x0a**, which is 2

Then, again 0x05: we go back to A2. Followed by A6 and **0x03 0x1d**
10-bit ZSCII character is 7d, which is `}`.

So, the flag is `Ph0wn {Improbability of 42}`.
To enter the flag, we'll ask to remove all spaces: `Ph0wn{Improbabilityof42}`.





