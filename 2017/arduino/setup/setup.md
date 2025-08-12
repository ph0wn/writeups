# How to

1. Install [Arduino IDE](https://www.arduino.cc). I used Arduino IDE 1.8.5. Be sure to use a Unix account in `dialout` group.
2. Load `wifi_challenge.ino`
3. Verify
4. Compile
5. Upload the program on an Arduino Uno R3
6. Check the serial monitor is responsive

Don't forget to bring the USB-A to USB-B cables.

## Compiling

I have warnings in `wifi_drv.cpp`: don't care.

```
/home/axelle/softs/arduino-1.8.5/libraries/WiFi/src/utility/wifi_drv.cpp: In static member function 'static uint8_t WiFiDrv::getEncTypeNetowrks(uint8_t)':
/home/axelle/softs/arduino-1.8.5/libraries/WiFi/src/utility/wifi_drv.cpp:451:10: warning: converting to non-pointer type 'uint8_t {aka unsigned char}' from NULL [-Wconversion-null]
   return NULL;
          ^
/home/axelle/softs/arduino-1.8.5/libraries/WiFi/src/utility/wifi_drv.cpp: In static member function 'static int32_t WiFiDrv::getRSSINetoworks(uint8_t)':
/home/axelle/softs/arduino-1.8.5/libraries/WiFi/src/utility/wifi_drv.cpp:476:10: warning: converting to non-pointer type 'int32_t {aka long int}' from NULL [-Wconversion-null]
   return NULL;
```

## Which port is the board on?

Unplug / Plug the Arduino and check `dmesg`:

```
[1309067.019100] usb 2-1.1.4: Manufacturer: Arduino (www.arduino.cc)
[1309067.019110] usb 2-1.1.4: SerialNumber: 5573630383935140A032
[1309067.019412] cdc_acm 2-1.1.4:1.0: ttyACM0: USB ACM device
```

it is on `/dev/ttyACM0` (select with Tools > Port)


## Uploading

Press "Upload"... it should go to 'Done uploading'.


## Serial Monitor

You will see:

```
Attempting to connect to WPA network
Couldn't get a wifi connection
```

which is normal! As we have no wifi!

Close Serial Monitor

## How to generate the password for stage 2

The flag for stage2 is not in cleartext, but uses a very basic encoding (nevertheless, that will probably force participants to do AVR reversing!).

The "encoding" consists in pointing to the right letter in a table.
To generate the code to copy & paste in the .ino, I have written a small Python script generator:

```bash
python generate-pass.py
```

The code must be copy / pasted appropriately in the .ino.
Do not forget to add the code for the tables too.

```c
char minuscule[] = "abcdefghijklmnopqrstuvwxyz";
char chiffre[] = "0123456789";
char signe[] = "{}";
```

Additionally, we can verify the generation is correct. That's what the `password` program does:

```bash
$ make
$ ./password
GOOD
```

If if says `BAD`, then there is a problem!!!

# Troubleshooting

If you can upload, try pressing the reset button.
