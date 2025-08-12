# Running Turbo Modula 

- [to run Turbo Modula programs under Linux](https://gitlab.com/gbrein/tnylpo): a utility to run CP/M-80 programs under Unix/Linux
- to install it, you need ncurses: `sudo apt-get install libncurses5-dev libncursesw5-dev`
- [Borland Turbo Modula 2](http://www.retroarchive.org/cpm/lang/lang.htm)
- [other tools that I didn't use](https://www.autometer.de/unix4fun/z80pack/ftp/)

To rename lowercase filenames to uppercase:

```
for i in $( ls | grep [A-Z] ); do mv -i $i `echo $i | tr 'A-Z' 'a-z'`; done
```

- Unzip [Borland Turbo Modula 2](http://www.retroarchive.org/cpm/lang/lang.htm)
- Copy all challenges files in that same directory (it's easier not to have to specify directories)
- Run *tnylpo* from inside the Turbo Modula 2 directory (otherwise, issues with missing files/directories)

## To compile a mod file

1. Copy it in ./tmod
2. From tnylpo, compile it (c) and then run it (r)

```
>C

Workfile name: greetest.mod
Imported: Greet

Compiled bytes:    51
M-code file A00:GREETEST.MCD produced.

>R

Run MCD-file: A00:GREETEST
```

## Output of program to read the database

```
>C

Compile file: readdb.mod      
Imported: Files
          Greet

Compiled bytes:   133
M-code file A00:READDB.MCD produced.

>R

Run MCD-file: A00:readdb.mcd
```

We decrypt the database:

```
Id : toto
Pwd: titi
Access denied.
Id: w2-aWYTP}
Pwd: ^"f\
Greeting: Much respect, Professor, dear author of Pascal, Modula(-2), Oberon...
Id: 
Pwd: "j3qq
Greeting: Greetings to a Turbo Modula-2 author! We will miss Borland...
Id: =_$f>YS
Pwd: wYdJ
Greeting: Welcome to a Turbo Modula-2 author! and congrats for Scala!
Id: 
Pwd: 
Greeting: YES!!! Ph0wn{Pico hack3d this place!} Greetings to Axelle & Ludo...
Id: WOF
Pwd: /jzTS
Greeting: (Intergalactic) Digital Research, Inc. Welcome, CEO, greetings to Dorothy...
```

# Step 2. Reversing the program

[Reverse Turbo Modula 2](https://github.com/Oric4ever/Reversing-Turbo-Modula2)

- Compile the disassembler: `gcc unassemble.c -o unassemble`
- Decompile: `unassemble greet.mcd`


## Running the solution for flag 2

```
Run MCD-file: A00:REVERSE

Id : f
Pwd: s
Access denied.
Id : Niklaus Wirth
Pwd: Oberon is as simple as possible
Msg: Much respect, Professor, dear author of Pascal, Modula(-2), Oberon...
Id : Peter Sollich
Pwd: Turbo Modula-2
Msg: Greetings to a Turbo Modula-2 author! We will miss Borland...
Id : Martin Odersky
Pwd: Scala4ever
Msg: Welcome to a Turbo Modula-2 author! and congrats for Scala!
Id : Pico le croco
Pwd: Ph0wn{TM2 hacks NW's work!}
Msg: YES!!! Ph0wn{Pico hack3d this place!} Greetings to Axelle & Ludo...
Id : Gary Kildall
Pwd: Gates is nuts!
Msg: (Intergalactic) Digital Research, Inc. Welcome, CEO, greetings to Dorothy...
```
