# Flash firmware

To flash the firmware in an hydrabus:
* unplug it
* use USB port 1
* keep the button next to USB port 1 pushed (DFU button) and plug the USB cable in the computer
* wait 2 seconds and release the button
* run:  `dfu-util -i 0 -a 0 -d 0483:df11 -D hydrafw.dfu`
* at the end of flashing process, unplug and plug the USB cable, Hydrabus is now a Hydrajet !


# Resources

- [dfu-util](https://dfu-util.sourceforge.net/releases/)

# Troubleshooting

`dfu-util: Cannot open DFU device 0483:df11 found on devnum 28 (LIBUSB_ERROR_ACCESS)`: use `dfu-util` as root.

To list DFU capable devices:
```
sudo ./dfu-util -l
dfu-util 0.11

...
Found DFU: [0483:df11] ver=2200, devnum=28, cfg=1, intf=0, path="3-4", alt=3, name="@Device Feature/0xFFFF0000/01*004 e", serial="207F36745853"
Found DFU: [0483:df11] ver=2200, devnum=28, cfg=1, intf=0, path="3-4", alt=2, name="@OTP Memory /0x1FFF7800/01*512 e,01*016 e", serial="207F36745853"
```

# Tested

- With `dfu-util 0.11` on Linux
