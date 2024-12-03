# OSINT challenges at Ph0wn 2024

## Corvette by Cryptax

This is an OSINT challenge. The challenge supplies a close view of an ECU:

```
This picture was taken from an ECU of a Chevrolet Corvette 1987.

- What manufacturer is it? 
- What MCU part model?
- What revision?
- What die revision?

The flag is ph0wn{manufacturer_model_revision_dierevnumber}, all lower case.
```

![Picture taken by Travis Goodspeed, provided for the challenge](./images/corvette.jpg)


### Solution

The chip is a generic microcontroller that was used in the late eighties by many car manufacturers. 

- Manufacturer: Motorola
- Model: MC68HC11
- Revision: A8
- Die revision number: C96N

- [Wikipedia page](https://en.wikipedia.org/wiki/Motorola_68HC11) - mentions A8 revision at the end.

### Guessing the manufacturer

You can find it by guessing that it's a Motorola and finding another die shot, or by the revision number, *C96N* (note it's C96N, and not N963!).

C96N is the die revision number, which appears in some photographs and datasheets.

### References on the web

- Here you can see it in the photo of a chip: [photo](https://partsmine.com/all/motorola-mc68hc11afn-microcontroller-versatile-8-bit-powerhouse-for-embedded-systems/)

- And here is a [public die photo of the same die](https://siliconpr0n.org/archive/lib/exe/detail.php?id=bercovici%3Amotorola%3Amc68hc11a1-c96n&media=bercovici:motorola:mc68hc11a1-c96n:mz.jpg)

If we search for "c96N Motorola ECU", we get MC68HC11

- [ECUs for Chevrolet Corvette 1987 on eBay](https://www.ebay.com/b/ECUs-Computer-Modules-for-1987-Chevrolet-Corvette/33
596/bn_7105723539)

- [M68HC11E data sheet](https://www.nxp.com/docs/en/data-sheet/M68HC11E.pdf). Revision A8 is mentioned at page 219 in a table that lists all revisions.
