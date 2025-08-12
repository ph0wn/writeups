# Flag digger

## Category

Hardware / forensics

## Difficulty

Easy - 100 / 200 points

## Author

Sylvain

## Flag

ph0wn{SP1_Fl4sh_M4st3r}

## Description

Like many IoT devices, your adventure starts with a small chip. Can you
find the flag ?

## Solution

Get the EEPROM, and dump it with bus pirate / hydrabus / whatever
This gives a squashfs image that can be mounted to get a pdg file
containing the flag.


Il y aura deux devices, et on aura assez d'hydrabus pour en preter si
jamais.
