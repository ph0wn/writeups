# Generating the .wav

```
$ go run distress.go --outraw ph0wn.raw --outwav beacon.wav --payload "p{Biip}"
Wrote 110 raw bits to ph0wn.raw
Wrote stereo I/Q WAV (10688 samples) to beacon.wav
```

Note that we have very little space for the flag.


# Notes

This is the COSPAS/SARSAT 406-Mhz Emergency Beacon Digital Controller

Table I page 9:

unique code for each beacon:

- 25-85: protected data field <-- payload
- 96-106: error correcting code <-- payload
- 107-112: national use or emergency code

Table II page 19:

distress.go is padding the message to 61 bits + 21 parity bits = 82 bits.

full stream is going to be:

1. Preamble: just ones (15 of them) --> table I bit synchronization
2. Morse bits: 000101111 --> table I frame synchronization
3. Message field (82 bits)
4. National emergency bits: 1111 --> page 10. "Four user-settable bits for nation use or for emergency codes" 1111 is for TEST.

In theory, after that, we could have "long message data" (113-144)

# The beacon

The COSPAS‑SARSAT 406 MHz emergency beacon is a global satellite‐based search‑and‑rescue transponder that operates on the 406 MHz band (UHF). It is used by both aviation and maritime users, but it is not tied to one specific domain; instead it can be installed on aircraft, ships, personal watercraft, ground vehicles, or even as a personal locator beacon (PLB) for hikers


# Quick spoiler

- `snap install urh`
- `urh`
- File, open the .wav
- Go to Analysis tab
- Decoding: Manchester II

In the Labels for message zone:

- Right Click, Configure Field Type
- Add a new type:
  caption = flag
  function = custom
  default display type = ASCII

In the bit zone:

- click on bit 25 and span all the way up to bit 106 included, then right click "Create Label".
- in the Label zone, right click, Edit
- name: flag
- start/end values should be okay as selected, 
- start/end values refer to view type: select ASCII (this changes start/end values)
- CONFIRM

Value will show the flag: `p{Biip}`
