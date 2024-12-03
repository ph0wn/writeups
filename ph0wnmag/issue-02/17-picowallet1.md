## PicoWallet 1: Driving the MPU by RMalmain

**Driving the MPU** is the first stage of **PicoWallet**, the cryptowallet system of Pico.
This part serves as an introduction to the system, to get used to the main drivers involved.

The final solution is available in the `solution_stage1.py` script.

### Environment

The first important step of the challenge is to correctly setup a reverse environment.
The description of the challenge explicitly gives the board (MPS2) and the 'specification' (the AN385).
Thus, after a quick search on the internet, we easily find the application note.
We can notably find inside:

- The architecture (the ARM Cortex M3)
- The memory mapping (we are especially interested in the `UART2` item, as explicitly given in the README)

We are now ready to start Ghidra with the latest version.
Ghidra proposes (as of v11.1) the wrong language to open the firmware.
It is important to select **the Cortex variant in little endian** to get a clean disassembly.

On debugging side, we can directly use the `run_picowallet.sh` script to run the target.
We could use `netcat` as shown in the README, but we decided to use `pwntools` instead to easily script the final payload.
Please check out the python code directly for the details on how to interact with the challenge using `pwntools`.

### Glossary

This section groups all the symbols we use in the write-up and link them to their address in memory, and possibly additional information like their command ID when it makes sense. 


#### Entrypoint commands

|   Name                | address      |  Command ID    |
|  :-------------:      |:------------:|:--------------:|
| help                  | `0x00000142` | `h`,`H`,`\x01` |
| get_first_wallet      | `0x00000186` |    `\x02`      |
| get_second_wallet     | `0x00000212` |    `\x03`      |
| pico_protect_handler  | `0x0000022a` |    `\x04`      |


#### `PicoProtect` sub-commands

|   Name                | address      |  Sub-command ID    |
|  :-------------:      |:------------:|:---------------|
| pico_protect_add        | `0x000011d8` |       `\x01`   |
| pico_protect_free       | `0x00001134` |       `\x02`   |
| pico_protect_chperm     | `0x0000117c` |       `\x03`   |
| pico_protect_configure  | `0x00001298` |       `\x04`   |

### Finding picowallet's entrypoint

The first step is to understand where the picowallet's entrypoint is and how it roughly works.
After a quick test with QEMU and providing some random bytes, we quickly get the `Unknown command.` error message.
We can simply look for this string in Ghidra and follow the cross-references.
There are two of them.

Both of them seem to be used in the default cause of some kind of `switch`.
We can deduce the function taking the string as parameter is some kind of `print`, writing to `UART2`.
We will go back to it later, in the stage 2 write-up.

The parameter of the switch seems to come from a function taking a buffer as parameter and a size.
After checking the underlying function, we see it's similar to a common pattern for UART drivers:

- check for a status byte.
- when it's ready, fetch the byte received and return it.
- repeat for as many bytes that must be fetched.

Since it's used as parameter of the switch, we deduce it's a function reading from UART and using it as input of the firmware.

To distinguish between the 2 cross-references, we simply try another option printing something (like the warning message, supposedly printed when receiving a `h` or `H`).

After a quick check, we are able to confirm the entry point.

### Trying to get the flag directly

The entry point can lead to other parts of the code, depending on the first byte received by UART2.
One of them looks very promising: the case `0x2`: It seems to fetch the first wallet, check for a magic value (`0xcafebabe`), and copy it to some buffer that will be printed if the magic value is correct.

However, after trying the payload `\x02\xbe\xba\xfe\xca`, the emulator seems to freeze on the `memcpy` happening after the first check.
A natural thing to try directly is to open a GDB server and check what happens. 
We observe the load instruction seems to trigger an exception.

QEMU has some tracing capabilities, and is able to show interrupts and exceptions taken at runtime.
After using the flag `-d int`, we quickly see this after one of the load instructions in the loop:

```
Taking exception 4 [Data Abort] on CPU 0
...at fault address 0x200000c0
...with CFSR.DACCVIOL and MMFAR 0x200000c0
...taking pending nonsecure exception 4
...loading from element 4 of non-secure vector table at 0x10
...loaded new PC 0x641
```
A `MemManage` fault is getting triggered (exception 4).
We can also notice it happens at the 65th iteration of the loop, which corresponds to the location of the first wallet's password.

Googling some terms (like `DACCVIOL`) and looking at the documentation of the `PMSAv7` shows it happens because of an access denied by the MPU.

Another string points to some protection-related operation: `Error while handling PicoProtect Driver request.`.
Still using xrefs, we find a sub-command handler when issuing a `\x04` command.

### First meeting with `PicoProtect`, the MPU driver

This is where the core of the first stage is.
We will now have a look to the functions called in the sub-command handler of the `PicoProtect` driver (the one associated to the command `\x04`)

The first thing we can notice is that one of these command's functions is called in the init phase of the entrypoint (the one linked to the sub-command `\x01`).
We can reasonably think this initialization function is performing some operation to protect the flag, which would explain what we observed in the previous section.

If this theory is correct, we can at least tell the third argument is an address and the fourth one a size (since it fits the password's size).

It is now time to reverse the function to understand what the two first arguments are.
Reversing this function tells us that the first parameter seems to be used as an index for arrays and the second one to set a hard value given to a called function.

The first argument is in fact used as an ID. The first array access is used to check if the ID has already been "allocated". If not, the function continues and marks the ID allocated when the function returns successfully. 
We will call this ID the memory region ID.

The second argument selects the kind of protection we want, either enabled (everyone can access the memory region) or disabled (no one can access the memory region).

Since there is an allocation check, it is not possible to re-add the same region at the same index.
The driver also checks for overlapping regions, denying adding a new region overlapping with another one.

The PicoProtect command handler shows there are other functions that are related to the `PicoProtect ` driver operations.
After spending some time reversing the different functions, we find what the sub-commands are roughly doing:

- `pico_protect_free` (sub-command `\x02`): free a `PiroProtect` region.
- `pico_protect_chperm` (sub-command `\x03`): change the permission of an existing PiroProtect region.
-  `pico_protect_configure` (sub-command `\x04`): set some global value (this will be discussed in the stage 2 writeup)

Everything has been put in the glossary with the corresponding firmware addresses.


### Getting the flag after configuring correctly the MPU

The previous section makes quite clear what the initialization phase of the entrypoint is doing.
It is in fact protecting the flag (associated with the memory region ID `\x02`) and making it inaccessible.

Thus, we can conclude there are multiple ways to un-protected the memory region protected by PicoProtect:
- Either free the memory region with ID 2 with `pico_protect_free`.
- Or change the permissions of the memory region with ID 2 with `pico_protect_chperm`.

We provide the two possible ways to get the flag in the attached solve scripts.

After the memory region has been configured correctly, we can simply fetch the flag again like we did with our first attempt.
This time, the flag appears correctly:

```
[+] Opening connection to chal.ph0wn.org on port 9250: Done
b'Requesting first wallet...\n'
b'Password is correct. Opening first wallet...\n'
b'    - ID: 91c7d99954dfed26fa80ca1bc323f03f\n'
b"    - Comment: November's salary\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\n"
b'    - Key: ph0wn{UnpR0t3Ct_tH3_pR0t3ct10N}\x00\n'
[*] Closed connection to chal.ph0wn.org port 9250
```

The final payload (with comments) can be found in the solve scripts.
