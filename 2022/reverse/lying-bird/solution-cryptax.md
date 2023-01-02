# MI5 Stage 1

- Category: Reverse
- Author: letitia
- Points: 458
- Solves: 8

# Description

As you know, Pico le Croco is a crocodile, thus a carnivorous animal, feeding mostly on vertebrates such as fish, reptiles, birds and mammals. He'd very much like to eat the bird in this PDF...

Help him, and get a flag.

PS. This is not steganography.

SHA256: `f8b469e6e39afd2bf1f978ae1e30e49196a6f53060432f466a69ef25afc35465`

The file is in `./files/chall.pdf`

# Solution

## PDF Viewer

When we view the PDF it displays a quote from Alfred Tennyson and the picture of bird.

```
“A lie that is half-truth is the darkest of all lies.”
Alfred Tennyson
```

The legend for the picture says "This bird will be important to you" and is likely to be a hint.


## Extracting the ELF

Binwalk outputs a surprising AARCH64 string, but does not detect any embedded binary.

```
$ binwalk ../public/chall.pdf 

DECIMAL       HEXADECIMAL     DESCRIPTION
--------------------------------------------------------------------------------
0             0x0             PDF document, version: "1.5"
8417723       0x8071BB        Unix path: /usr/lib/gcc-cross/aarch64-linux-gnu/5/../../../../aarch64-linux-gnu/lib/../lib/crt1.o
```

We load the PDF in a hexadecimal editor and notice `NOTELF` followed by lots of bytes.

```
00805680  34 31 30 32 39 33 0a 7f  4e 4f 54 45 4c 46 02 01  |410293..NOTELF..|
00805690  01 00 00 00 00 00 00 00  00 00 02 00 b7 00 01 00  |................|
```

This is why Binwalk does not see the ELF: ELF magic has been modified to `NOTELF` and Binwalk does not find it.
The correct magic for ELF binaries is `7f 45 4c 46`. We patch the binary:

1. T appears at offset 0x80568a (8410762 in decimal)

```
xxd ../public/chall.pdf | grep NOTELF
00805680: 3431 3032 3933 0a7f 4e4f 5445 4c46 0201  410293..NOTELF..
```
2. Truncate the file at this offset:

`dd if=chall.pdf of=myelf.elf skip=8410762 bs=1`

3. Patch the `T` with 0x7f to match a correct ELF magic header.

`echo "7f" | xxd -r -p - myelf.elf`

Now, `file` says it is an ARM64 binary:

```
ELF 64-bit LSB executable, ARM aarch64, version 1 (SYSV), dynamically linked, interpreter /lib/ld-linux-aarch64.so.1, for GNU/Linux 3.7.0, BuildID[sha1]=8d665c162c570767758c312922798f0cdf8efc1c, not stripped
```

## Running the binary

As I don't have an ARM64 device at hand (only ARM 7 sorry), I use qemu to run the binary:

`qemu-aarch64 -L /usr/aarch64-linux-gnu/ ../myelf.elf`

It outputs part of the flag, but is unable to finish.

```
$ qemu-aarch64 -L /usr/aarch64-linux-gnu/ ./myelf.elf
ERROR: ld.so: object 'libgtk3-nocsd.so.0' from LD_PRELOAD cannot be preloaded (cannot open shared object file): ignored.
p
h
0
w
n
{
X
X
X
X
X
B
2
3
a
D
b
8
3
```

## Reversing the binary

We have a partial flag: `ph0wn{XXXXXB23aD83...`. 
With Radare2, we search for `ph0wn`:

```
[0x00411058]> f~ph0wn
0x00411058 144 str._UTF_ph0wnXXXXXB23aDb83l23s9FPl0d
```

There is apparently a string beginning with `UTF` containing `ph0wn`. It is used in the `main()`:

```
[0x00411058]> axt str._UTF_ph0wnXXXXXB23aDb83l23s9FPl0d 
main 0x4007fc [STRING] add x0, x0, 0x58
main 0x40085c [STRING] add x1, x1, 0x58
main 0x400878 [STRING] add x0, x0, 0x58
[0x00411058]> pdf @ main
            ; UNKNOWN XREF from entry0 @ +0x30
┌ 352: int main (int argc, char **argv, char **envp);
...
      ╎│   0x004007fc      00600191       add x0, x0, 0x58            ; 0x411058 ; U"-UTF<ph0wn{XXXXXB23aDb83l23s9}FPl0d}"
│      ╎│   0x00400800      001c40b9       ldr w0, [x0, 0x1c]          ; 0xd8 ; 216

```

The full string is apparently `-UTF<ph0wn{XXXXXB23aDb83l23s9}FPl0d}`.
The string is strange because it has 2 closing brackets.

Let's have a look at the algorithm inside `main()`.
This is the output Ghidra produces.

```c
void main(void)

{
  int iVar1;
  time_t tVar2;
  long lVar3;
  time_t atStack72 [8];
  long local_8;
  
  local_8 = __TMC_END__;
  tVar2 = time(atStack72);
  srand((uint)tVar2);
  iVar1 = rand();
  while (_var1 < var2._16_4_ / 2) {
    if (_var1 < iVar1 % 7 || _var1 < var2._28_4_ / 2) {
      lVar3 = (long)_var1;
      _var1 = _var1 + 1;
      printf("%c\n",(ulong)*(uint *)(var2 + lVar3 * 4));
    }
  }
  if (local_8 != __TMC_END__) {
                    /* WARNING: Subroutine does not return */
    __stack_chk_fail(0);
  }
  return;
}
```

1. Initialization of randomness
2. `var2` is the string `-UTF<ph0wn...`. Tell Ghidra to make it a string if it does not detect it automatically. It transforms the loop to `while (_var1 < _DAT_00411068 / 2) {`
3. The loop runs until we reach  `_DAT_00411068 / 2`. This is `0x3c / 2` which corresponds to 30 in decimal.
4. In `-UTF<ph0wn{XXXXXB23aDb83l23s9}FPl0d}`, index 30 contains `F`. This means we will process the string up to the **first** closing bracket `}` (just before `F`)
5. During each loop iteration, we print a character of the flag. The counter `_var1` is incremented.

The function does not end because of the bogus end `__TMC_END__`.

## Fixing the PDF

A PDF is walked from the catalog down. When you find the catalog, it points to the
page tree obj 6. 

```
6 0 obj
<</Type/Pages/Count 1/Kids[3 0 R 8 0 R]>>
endobj
```

obj 6 is weird because page count is 1, but there are 2 entries in /Kids. That implies there
should be 2 pages. We fix this.

```
6 0 obj
<</Type/Pages/Count 2/Kids[3 0 R 8 0 R]>>
endobj
```

Now, there is a second page in the PDF, but strangely it has the same content.
We look again in the PDF format: the first pages references object 3, the second page references object 8.
If we look at object 3, its content references object 4, and same for object 8

```
3 0 obj
<</Type/Page/Contents 4 0 R/Resources 2 0 R/MediaBox[0 0 612 792]/Parent 6 0 R>>
endobj
...
8 0 obj
<</Type/Page/Contents 4 0 R/Resources 7 0 R/MediaBox[0 0 612 792]/Parent 6 0 R>>
endobj
```

This explains why we have the same content on both pages. 
On the other side, we realize obj 9 is a text stream that is never used. We fix this

We fix the second page to use object 9:

```
8 0 obj
<</Type/Page/Contents 9 0 R/Resources 7 0 R/MediaBox[0 0 612 792]/Parent 6 0 R>>
endobj
```

Now, the hidden text appears, saying `Find the hidden text to replace the XXXXX characters in the flag`.


## Finding the hidden text

If you look at the xref table, the
number of entries is stated to be 15, but there are 16 entries. 

```
xref
0 15
0000000000 65536 f 
0000000018 00000 n 
0008375520 00000 n 
0008375608 00000 n 
0008375705 00000 n 
0008376448 00000 n 
0008376577 00000 n 
0008376635 00000 n 
0008376694 00000 n 
0008376791 00000 n 
0008377180 00000 n 
0008377785 00000 n 
0008409680 00000 n 
0008409989 00000 n 
0008410036 00000 n 
6a556e6330 fffff f
```

Also note the revision number of `fffff` should make no sense, and the location in
memory this xref points to, `6a556e6330` is way too high and past the last
byte of this file! It is greater than the location pointed to by `startxref`,
which is impossible!

```
$ echo "6a 55 6e 63 30" | xxd -r -p
jUnc0
```

A junco happens to be the bird in the picture.
We replace it in the partial flag. 

The flag is `ph0wn{jUnc0B23aDb83l23s9}`

## Failed attempts

I installed [PDFMiner.Six](https://github.com/pdfminer/pdfminer.six). It displays the text in the PDF but does not see the additional hidden content.