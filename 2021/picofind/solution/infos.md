## PicoFind (Sebastien Andrivet)

The challenge have people work on touch LCD screen which are typically used for 3D prtinters. Those LCD screens have the particularity to have an *embedded microcontroller (ARM) + Flash, SRAM and EEPROM*.

To program those screens, you need to write given files in a given format, put that on a SD card, boot the device and the screen sets itself up. It is possible to customize image sequences (with active zones, widgets).

The screens are powered with 5V.

Communication with host (e.g. 3D printer) is done by **serial port**, with a proprietary protocol [non official translation](http://sebastien.andrivet.com/en/posts/dwin-mini-dgus-display-development-guide-non-official/)

### Pico Find 1

Navigate through the pages to find a hidden zone and access the page with the flag.

### Pico Find 2

The page with the flag cannot be navigated to. You need to get it by other means: serial port. Understand the protocol and send the right commands.
