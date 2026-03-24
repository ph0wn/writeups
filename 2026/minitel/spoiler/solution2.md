# Minitel - Stage 2

For this challenge, you are faced with a very simple authentication service. 
The challenge is called minipwn, which suggests that it will likely involve a pwn-type vulnerability.

An obvious first idea is to try command injection. However, you quickly realize that characters such as &, ', ;, \, and similar payloads do not produce any useful result.

The hint that should make you think about a format string vulnerability is that the service repeats the password you enter. 
Because the input is echoed back, it becomes possible to test a simple payload like %x to see how the program behaves and whether it leaks stack values.

Now the next step is to figure out how to authenticate.

The most obvious payload to try first is a sequence like %x %x %x ... in order to dump as many stack values as possible.

The first difficulty is the password length limit, which is 40 characters. This prevents us from retrieving many pointers from the stack.

However, among the first leaked values there is one pointer that falls into the .data section, which looks interesting. For example:%x %x %x %x %x %x produces: fffbd794 28 80491a2 fffbd7a4 eabed6f0 804c040

The pointer 0x804c040 looks promising, so we try to print it as a string: %x %x %x %x %x %s which gives something like: ffe229a4 28 80491a2 ffe229b4 f425a6f0 Mot de passe juste a cote ...

This output suggests that the actual password is located right next to this memory area. In other words, the password is likely stored very close in memory to address 0x804c040, somewhere in the same data segment.

The next step is to bypass the 40-byte input limit.

With a format string vulnerability, it is possible to directly reference specific positions on the stack. The syntax is: %POS$x to print the value at a given stack position as hexadecimal %POS$s to interpret the value at that position as a pointer to a string.

By iterating through stack positions this way, we can inspect many values even with a short payload.

While exploring the stack, several pointers appear in the form 0x804...., which usually corresponds to addresses in the program’s data segment. Eventually, the pointer 0x804c040 appears at position %41$x.

Continuing further through the stack, at position %49$x we find another pointer: 0x804c060. This address is very close to the previous one, which already hinted that the password was stored nearby in memory.

Finally, we simply print that pointer as a string: %49$s and this reveals the password: PirateDancer

Then entering the password gives you the flag : ph0wn(neverForgetSimpleStuff)

