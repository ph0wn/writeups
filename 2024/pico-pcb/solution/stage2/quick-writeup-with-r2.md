# Pre-requisite

- `r2pm -ci uf2`

# Lauching r2

- `r2 -a arm -b 16 -m 0x10000000 ./extracted_data.bin`


# Search for congrats

We want to have cross references (aar)

```
[0x10000000]> e anal.strings = 1
[0x10000000]> aar
[0x10000000]> aap
[0x10000000]> / Congrats
0x1000697c hit2_0 . (* to END): Congrats! Flag is ph0wn{.
[0x10000000]> s hit2_0
[0x1000697c]> axt
fcn.10000578 0x10000584 [ICOD:--x] ldr r3, [0x10000628]
[0x1000697c]> s fcn.10000578
```

# Identify the main, printf

Rename fcn.10000578 as main:

```
[0x10000578]> af main
```

Rename the printf function:

```
0x10000612      0948           ldr r0, [str.VROOOOOOOOOOOOOM__You_started_the_engine_] ; [0x10000638:4]=0x100069b8 ".VROOOOOOOOOOOOOM! You started the engine!"
│  ╎        0x10000614      03f0ecfc       bl fcn.10003ff0
[0x10000578]> s fcn.10003ff0
[0x10003ff0]> af printf
```

# 3 choices

Notice we have 3 choices:

```
0x100005fa      3228           cmp r0, 0x32                ; 2'2' ; 50
│ │╎╎  └──< 0x100005fc      c5d0           beq 0x1000058a
│ │╎╎   ╎   0x100005fe      3328           cmp r0, 0x33                ; 2'3' ; 51
│ │╎└─────< 0x10000600      d7d0           beq 0x100005b2
│ │╎    ╎   0x10000602      3128           cmp r0, 0x31                ; 2'1' ; 49
│ │╎    └─< 0x10000604      f6d1           bne 0x100005f4
```

# Hidden menu

We are interested in the third menu, which is hidden. It jumps to 0x100005b2.

```
┌─────> 0x100005b2      6946           mov r1, sp
│   ╎││╎│   0x100005b4      1d4b           ldr r3, [0x1000062c]        ; [0x1000062c:4]=0x100069f4 "37*0(.&7*&*' $16($7,*"
│   ╎││╎│   0x100005b6      0a00           movs r2, r1
│   ╎││╎│   0x100005b8      91cb           ldm r3!, {r0, r4, r7}
│   ╎││╎│   0x100005ba      91c2           stm r2!, {r0, r4, r7}
│   ╎││╎│   0x100005bc      03cb           ldm r3!, {r0, r1}
│   ╎││╎│   0x100005be      03c2           stm r2!, {r0, r1}
│   ╎││╎│   0x100005c0      1b78           ldrb r3, [r3]
│   ╎││╎│   0x100005c2      1370           strb r3, [r2]
│   ╎││╎│   0x100005c4      1621           movs r1, 0x16
│   ╎││╎│   0x100005c6      0ca8           add r0, var_30h
│   ╎││╎│   0x100005c8      fff796ff       bl fcn.100004f8
│   ╎││╎│   0x100005cc      0400           movs r4, r0
│   ╎││╎│   0x100005ce      1623           movs r3, 0x16
│   ╎││╎│   0x100005d0      4522           movs r2, 0x45               ; 'E'
│   ╎││╎│   0x100005d2      0ca9           add r1, var_30h
│   ╎││╎│   0x100005d4      06a8           add r0, var_18h
│   ╎││╎│   0x100005d6      fff7c3ff       bl fcn.10000560
│   ╎││╎│   0x100005da      152c           cmp r4, 0x15                ; 21
│  ┌──────< 0x100005dc      06d1           bne 0x100005ec
│  │╎││╎│   0x100005de      1522           movs r2, 0x15
│  │╎││╎│   0x100005e0      6946           mov r1, sp
│  │╎││╎│   0x100005e2      06a8           add r0, var_18h
│  │╎││╎│   0x100005e4      05f0b2ff       bl fcn.1000654c
│  │╎││╎│   0x100005e8      0028           cmp r0, 0
│ ┌───────< 0x100005ea      12d0           beq 0x10000612
```

Notice a strange string (instruction at 0x100005b4, held in 0x100069f4): `37*0(.&7*&*' $16($7,*`
Then, there are 3 different functions:

- fcn.100004f8: this one prints the 3rd menu
- fcn.10000560: performs an XOR
- fcn.1000654c: complicated, but we know that if it returns 0, we jump to 0x10000612 which is a success (starts the engine)

## XOR algorithm

```
[0x10000578]> pdf @ fcn.10000560
            ; CALL XREF from main @ 0x100005d6(x)
┌ 22: fcn.10000560 ();
│           0x10000560      30b5           push {r4, r5, lr}
│           0x10000562      002b           cmp r3, 0
│       ┌─< 0x10000564      06dd           ble 0x10000574
│       │   0x10000566      0024           movs r4, 0
│       │   ; CODE XREF from fcn.10000560 @ 0x10000572(x)
│      ┌──> 0x10000568      0d5d           ldrb r5, [r1, r4]
│      ╎│   0x1000056a      5540           eors r5, r2
│      ╎│   0x1000056c      0555           strb r5, [r0, r4]
│      ╎│   0x1000056e      0134           adds r4, 1
│      ╎│   0x10000570      a342           cmp r3, r4
│      └──< 0x10000572      f9d1           bne 0x10000568
│       │   ; CODE XREF from fcn.10000560 @ 0x10000564(x)
└       └─> 0x10000574      30bd           pop {r4, r5, pc}
```

We rename the XOR function:

```
[0x10000578]> af xor @ fcn.10000560
```

## Call to the XOR algorithm

The XOR function has 4 arguments: (1) a buffer, (2) another buffer, (3) the key 0x45, (4) the size (0x16)

```
0x100005cc      0400           movs r4, r0
│   ╎││╎│   0x100005ce      1623           movs r3, 0x16
│   ╎││╎│   0x100005d0      4522           movs r2, 0x45               ; 'E'
│   ╎││╎│   0x100005d2      0ca9           add r1, var_30h
│   ╎││╎│   0x100005d4      06a8           add r0, var_18h
│   ╎││╎│   0x100005d6      fff7c3ff       bl xor
```

## Decrypting

We have :

1. A strange string: at 0x100069f4
2. A key: 0x45
3. A length: 0x15 (or 0x16 with trailing null)
4. An XOR algorithm


```
[0x10000578]> px 0x15 @ 0x100069f4
- offset -  F4F5 F6F7 F8F9 FAFB FCFD FEFF  0 1  2 3  456789ABCDEF0123
0x100069f4  3337 2a30 281a 2637 2a26 2a27 2024 3136  37*0(.&7*&*' $16
0x10006a04  2824 372c 2a                             ($7,*
```

Now let's perform the XOR. To write, we need to activate io.cache: `e io.cache=true`. The `wox` command says to apply XOR at a given address for 0x15 bytes.

```
[0x100069f4]> e io.cache=true
[0x100069f4]> wox 0x45 @ 0x100069f4 0x15
[0x100069f4]> px 0x15 @ 0x100069f4
- offset -  F4F5 F6F7 F8F9 FAFB FCFD FEFF  0 1  2 3  456789ABCDEF0123
0x100069f4  7672 6f75 6d5f 6372 6f63 6f62 6561 7473  vroum_crocobeats
0x10006a04  6d61 7269 6f                             mario
```

## Flag

You get the password to enter the hidden menu. Run it, enter the password and you'll get the flag.
