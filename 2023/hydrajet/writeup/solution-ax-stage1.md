# HydraJet 1 by Phil242 and Cryptax

This challenge was created by *Phil242*. The write-up is by *Cryptax*.

## Description

```
Pico has just finished his beta version of Hydrajet.

His project remind you something ? Are you sure ?

Get the flag.
```

The challenge requires a **HydraJet** to borrow. 

## Using HydraJet

Actually, the device is a [Hydrabus](https://hydrabus.com) with a modified *firmware*.

We connect to the device:

```
$ picocom /dev/ttyACM0

> help
Available commands
   help           Available commands
   history        Command history
   clear          Clear screen
   show           Show information
   logging        Turn logging on or off
   sd             SD card management
   adc            Read analog values
   dac            Write analog values
   pwm            Write PWM
   frequency      Read frequency
   gpio           Get or set GPIO pins
   spi            SPI mode
   i2c            I2C mode
   1-wire         1-wire mode
   2-wire         2-wire mode
   3-wire         3-wire mode
   uart           UART mode
   agc            AGC mode (BETA)
   nfc            NFC mode
   can            CAN mode
   sump           SUMP mode
   jtag           JTAG mode
   random         Random number
   flash          NAND flash mode
   wiegand        Wiegand mode
   lin            LIN mode
   smartcard      SMARTCARD mode
   debug          Debug mode
```

We notice a new menu which does not exist on Hydrabus: AGC.

```
> agc
Device: UART1
Speed: 9600 bps
Parity: none
Stop bits: 1
uart1> help
Show UART parameters
   show           Show UART parameters
   read           Read byte (repeat with :<num>)
   hd             Read byte (repeat with :<num>) and print hexdump
   decode-agc     agc decoder (BETA)          ph0wn{th1s_m3nu_s33ms_n3w}
   scan           Measure baudrate (PC6)
   exit           Exit AGC mode
```

The flag is `ph0wn{th1s_m3nu_s33ms_n3w}`
