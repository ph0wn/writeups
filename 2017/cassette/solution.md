# Solution

## Install Euphoric
* `wget http://www.emucamp.com/oric/euphoric/windows/Euphoric.zip`
* `unzip Euphoric.zip`
* `wget -P Euphoric/roms/ https://github.com/Godzil/osXdk/raw/master/Osdk/_final_/Euphoric/ROMS/BASIC10.ROM`
* `cp CLASSIFIED.recording237849.part2.unintelligible Euphoric/tapes/TAPE.WAV`
* `vim Euphoric/EUPHORIC.INI`
    * change these lines:

```
RomPath=c:\roms
TapePath=c:\tapes
DiskPath=c:\disks
Computer=Oric1
Oric1Rom=BASIC10.ROM
```

## Install Dosbox (ubuntu)
* `sudo apt-get install dosbox`
* launch dosbox if it has never been launched before
* `vim ~/.dosbox/dosbox-0.74.conf`
    * add at the end:

```
keyb en
mount c:  <path-to-repository>/challenges/cassette/Euphoric
c:
set ORIC=c:\
euphoric.exe
```

## Run the tape
* launch dosbox
    * `F1`
        * Tape HW
        * Select tape -> `TAPE`
    * `CLOAD ""`
    * `RUN`


## Flag
`Ph0wn{!TFOSORCIM}`
