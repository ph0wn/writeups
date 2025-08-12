# Double Rainbow Stage 1

Solved by 8 teams

We download the SD card image and list its partitions:

```
$ fdisk -l double-rainbow-level1.img 
Disk double-rainbow-level1.img: 1,8 GiB, 1890910208 bytes, 3693184 sectors
Units: sectors of 1 * 512 = 512 bytes
Sector size (logical/physical): 512 bytes / 512 bytes
I/O size (minimum/optimal): 512 bytes / 512 bytes
Disklabel type: dos
Disk identifier: 0xae420040

Device                     Boot   Start      End  Sectors  Size Id Type
double-rainbow-level1.img1 *       4096   135167   131072   64M  c W95 FAT32 (LBA)
double-rainbow-level1.img2       147456  3074047  2926592  1,4G  7 HPFS/NTFS/exFAT
double-rainbow-level1.img3      3074048  3076095     2048    1M 70 DiskSecure Multi-Boot
double-rainbow-level1.img4      3076096 15523839 12447744    6G  5 Extended
double-rainbow-level1.img5      3080192 15523839 12443648    6G  7 HPFS/NTFS/exFAT
```

The interesting program is likely to be in the last partition, so we mount it:
`sudo mount -t ntfs -o loop,ro,offset=$((3080192*512)) double-rainbow-level1.img /tmp/image/`

Then, we list recent files : 

```
$ find . -mtime -10 -print

./Users/DefaultAccount/AppData/Local/DevelopmentFiles/DoubleRainbow1-uwpVS.Debug_ARM.sebas
./Users/DefaultAccount/AppData/Local/DevelopmentFiles/DoubleRainbow1-uwpVS.Debug_ARM.sebas/AppxManifest.xml
...
./Users/DefaultAccount/AppData/Local/Packages/DoubleRainbow1-uwp_r63asj4s1fa58
./Users/DefaultAccount/AppData/Local/Packages/DoubleRainbow1-uwp_r63asj4s1fa58/AC
```

We quickly notice interesting directory `./Users/DefaultAccount/AppData/Local/DevelopmentFiles/DoubleRainbow1-uwpVS.Debug_ARM.sebas` with `DoubleRainbow1.winmd` and `DoubleRainbow1Lib.dll` which are .NET applications.

The hardest step is to find a .NET decompiler that will really work on my Linux host :(
I end up with [Dis#](http://www.netdecompiler.com/), which is buggy under Wine, but mostly works:

`/opt/wine-stable/bin/wine64 /home/axelle/.wine/drive_c/Program\ Files\ \(x86\)/NETdecompiler/DisSharp/Dis#.exe`

For some reason, the decompiled source is displayed white on white, so I need to "Save Source" for DoubleRainbowLib to see the source code.

I quickly locate this interesting piece of code:

```c#
 public Challenge()
        {
            code_ = new int[5];
            codeIndex_ = 0;
            codeLock_ = new System.Object();
            code1_ = new int[] { 0, 1, 0, 4, 3 };
            new string[2][0] = "ph0wn{our-hearts";
            new string[2][1] = "-in-vain}";
            level1Flag = new string[2];
            new string[2][0] = "ph0wn{fell-under";
            new string[2][1] = "-your-spell}";
            level2Flag = new string[2];
            controller_ = new System.Device.Gpio.GpioController();
            lcd_ = new Iot.Device.CharacterLcd.Lcd1602(19, 26, new int[] { 25, 24, 23, 18 }, -1, 1.0F, -1, null);
            OpenLEDs();
        }
```

This gives two flags. We can try them but they are unfortunately placeholders.

- Level 1 flag: ph0wn{our-hearts-in-vain}
- Level 2 flag: ph0wn{fell-under-your-spell}

The real flag is given by `code1_ = new int[] { 0, 1, 0, 4, 3 };`.

```
new string[5][0] = "orange";
            new string[5][1] = "red";
            new string[5][2] = "white";
            new string[5][3] = "green";
            new string[5][4] = "blue";
```

So, color code should be Orange - Red - Orange - Blue - Green

Nota. This was tested on a preliminary version of Double Rainbow. The flag, or the color sequence might have been different for Ph0wn 2019.
