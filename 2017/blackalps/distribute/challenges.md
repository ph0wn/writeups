= BlackAlps badge I - Xtensible reversing
== Category : Reverse
== Difficulty : Medium 300
== Description : 
    This little device is locked and does not allow us to connect. We
    were able to dump its firmware, but it seems we are not able to
    reverse its authentication algorithm. Can you make it ?
    
   Flag Format : Ph0wn{password}

sha256 blackalps.elf
`7bad85feb5bdca21163ac7545d42b47e099d9aa6d50a49e7555a9b425c2ef9a3`

= BlackAlps badge II - Let's get graphical
== Category : Reverse/Forensics
== Difficulty : Medium 300
== Description :
    The firmware contains a flag that should have been displayed on the
    screen, if only the developper had enough time to implement the code
    to display it...

    NB. This challenge is feasible without part 1.
    Flag format is : PH0WN{...}  (uppercase Ph0wn)

= BlackAlps badge III - Game over
== Category : Reverse/Pwn
== Difficulty : Hard 500
== Description :
    Congrats ! Now that you have made the first two steps, you can now play
with the last part of the challenge and recover the last flag.
Hint : look for the "====FLAG GOES HERE====" symbol in the binary

    to play with the device and discover the last flag.

