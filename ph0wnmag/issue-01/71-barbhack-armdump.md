# Barbhack 2023 -- Dump all the ARM things! by Khauchy

This write-up was submitted by *Khauchy*.

Challenge authors: *Azox* and *Balda*, everything is provided in their [Github](https://github.com/0x8008135/talks/tree/main/23-barbhack/).

We are provided with an STM32 device, that was already plugged to an [Hydrabus](https://hydrabus.com/) through the SWD interface.
When the device is powered on, we can see that the LED blink in a (seemingly) random order.

## Firmware dump

We can plug the hydrabus and dump the firmware using the SWD interface, e.g. with the python API (most of this script was also provided by the organizers, thanks!):

```python
import pyHydrabus

OUTFILE = "firmware_dumped.bin"

s = pyHydrabus.SWD()
s.bus_init()

#Power up debug domain
s.write_dp(4, 0x50000000)

#Scan the SWD bus
for i in range(1):
    print(f"AP {i} IDCODE: {hex(s.read_ap(i, 0xfc))}")
# we get:
# AP 0 IDCODE: 0x4770031

AP_ADDRESS = 0

# Initialize AP
CSW = s.read_ap(0, 0)
CSW |= 0b010 # enable 32-bit transfer
CSW |= 1<<6 # set DeviceEn[6]
s.write_ap(AP_ADDRESS,0,CSW)

def read_cpu_address(address: int):
    s.write_ap(AP_ADDRESS, 0x4, address)
    return s.read_ap(0, 0xc)

def write_cpu_address(address: int, data: int):
    s.write_ap(AP_ADDRESS, 0x4, address)
    s.write_ap(AP_ADDRESS, 0xc, data)

def halt_cpu():
    """
    Halt CPU by writing to MEM-AP DRW: set bits C_HALT[1] and C_DEBUGEN[0]

    This ensures that the CPU will not access the flash concurrently.

    This can be reversed by writing instead 0xA05F0000 at the same address.
    """
    #Write to MEM-AP DRW, 
    write_cpu_address(0xE000EDF0, 0xA05F0003)

halt_cpu()

#Get those information from the memory map
# see page 52 of https://www.st.com/resource/en/reference_manual/rm0377-ultralowpower-stm32l0x1-advanced-armbased-32bit-mcus-stmicroelectronics.pdf
FLASH_BASE_ADDRESS = 0x08000000
FLASH_SIZE = 0x2000

buff = b''

for i in range(0, FLASH_SIZE,4):
    val = read_cpu_address(FLASH_BASE_ADDRESS+i).to_bytes(4, byteorder="little")
    buff = buff+val

with open(OUTFILE, 'wb') as fd:
    fd.write(buff)
```

After executing this script, we got the firmware that we can reverse.

## Reverse

We can load the dumped firmware in ghidra.
When loading the binary:

- for the language, choose ARM Cortex little-endian
- in the options, don't forget the base address. It's 0x08000000 instead of 0x0

Do not analyze it yet.
First, we must use the [SVD-Loader.py](https://github.com/leveldown-security/SVD-Loader-Ghidra) script to load the memory map of the board's peripherals.
Then, download the [SVD for the specific board](https://github.com/cmsis-svd/cmsis-svd/).
In the script window ("Window", "Script manager", then double-click on "SVD Loader"), load the downloaded SVD file.
You can then add the SRAM memory map in the memory map window ("Window", "Memory Map"; as you can see, it has already been filled by `SVD-Loader.py`).
According to the reference manual, it's located at offset `0x20000000`, and at most `0x5000` bytes.

Now, you can auto-analyze the binary.
Don't forget to tick the "ARM Aggressive Instruction Finder", which will find more functions.

First, we can search for strings.
We see an `"Init done\r\n"`, this looks interesting!
This string is called in `FUN_08000388`, which has the following structure:

```c
void FUN_08000388(void)

{
  // variables initialization

  FUN_0800187c(puVar1,PTR_s_Init_done_080004ac,0xb,0xffffffff);
  do {
    iVar5 = 0;
    do {
      cVar3 = '\0';
      do {
        cVar4 = cVar3 + '\x01';
        FUN_08000540(cVar3);
        FUN_08000b58(100);
        cVar3 = cVar4;
      } while (cVar4 != '\b');
      cVar3 = '\a';
      do {
        cVar4 = cVar3 + -1;
        FUN_08000540(cVar3);
        FUN_08000b58(100);
        cVar3 = cVar4;
      } while (cVar4 != -1);
      FUN_080005ac(PTR_DAT_080004b0 + iVar5 * 8);
      iVar5 = i + 1;
      FUN_08000b58(100);
    } while (iVar5 != 9);
  } while( true );
}
```

Let's look into it!
The inner `do {} while(iVar5 != 9);` looks like a `for` loop, and its content has the following structure:

- call `FUN_08000540` and `FUN_08000b58` 8 times, with increasing arguments for `FUN_08000540`;
- do it again, with decreasing arguments;
- call `FUN_080005ac` with an argument depending on the outer counter;
- call `FUN_08000b58` (always with the same argument: `100`).

The outer loop is always executed.
Maybe this is the `main` function of the board, that keeps on repeating the blinking of the LEDs?

I first tried to analyze `FUN_08000540` and `FUN_08000b58`, but they seemed complex.
Before diving into them, I looked into `FUN_08005ac`.

This function is called with address `0x080004b0`, which contains the address `0x08001d23`.
This address contains a table filled with `0x00` and `0xff` (I simply changed its type to `char[64]` to better display it):

```
s__08001d23  XREF[3]:     FUN_08000388:0800047a(*), 
                          FUN_08000388:0800047e(*), 
                                                                               080004b0(*)  
        08001d23 00 ff 00        char[64]   ""
                 00 00 00 
                 ff 00 00 
           08001d23 [0]           '\0', FFh,'\0','\0',
           08001d27 [4]           '\0','\0', FFh,'\0',
           08001d2b [8]           '\0', FFh,'\0','\0',
           08001d2f [12]          '\0','\0', FFh,'\0',
           08001d33 [16]          '\0', FFh,'\0', FFh,
           08001d37 [20]          '\0','\0','\0', FFh,
           08001d3b [24]          '\0','\0', FFh,'\0',
           08001d3f [28]           FFh, FFh,'\0', FFh,
           08001d43 [32]          '\0', FFh,'\0', FFh,
           08001d47 [36]          '\0','\0', FFh,'\0',
           08001d4b [40]          '\0','\0', FFh, FFh,
           08001d4f [44]          '\0','\0','\0','\0',
           08001d53 [48]          '\0', FFh,'\0', FFh,
           08001d57 [52]           FFh,'\0','\0','\0',
           08001d5b [56]          '\0', FFh,'\0', FFh,
           08001d5f [60]           FFh,'\0','\0','\0'
```

This looks suspicious!
My first idea was that it was a bitstring, where `\0` are 0 and `FFh` are 1.
I fired up a quick Python script:

```python
data = [ 0x00, 0xff, 0x00, 0x00, 0x00, 0x00, 0xff, 0x00, 0x00, 0xff, 0x00, 0x00, 0x00, 0x00, 0xff, 0x00, 0x00, 0xff, 0x00, 0xff, 0x00, 0x00, 0x00, 0xff, 0x00, 0x00, 0xff, 0x00, 0xff, 0xff, 0x00, 0xff, 0x00, 0xff, 0x00, 0xff, 0x00, 0x00, 0xff, 0x00, 0x00, 0x00, 0xff, 0xff, 0x00, 0x00, 0x00, 0x00, 0x00, 0xff, 0x00, 0xff, 0xff, 0x00, 0x00, 0x00, 0x00, 0xff, 0x00, 0xff, 0xff, 0x00, 0x00, 0x00
]
bitstring = [x // 255 for x in data]
bitstring_by_8 = [bitstring[i:i+8] for i in range(0, len(bitstring), 8)]
bytestring = [int("0b" + "".join(str(x) for x in item), 2) for item in bitstring_by_8]
print(bytes(bytestring).decode())
# BBQ-R0XX
```

And we got the flag: `BBQ-R0XX`!

Thanks again to Azox and Balda for organizing this workshop, I learned a lot about SWD and ARM reversing!
