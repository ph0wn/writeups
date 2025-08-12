# How to flash a sensor

- Use Proxmark3: `~/softs/proxmark3/client/proxmark3 /dev/ttyACM0`
- Copy the script `./flash-sensor.lua` to Proxmark's script dir: `~/softs/proxmark3/client/scripts/`
- Run: `script run flash-sensor`

```
proxmark3> script run flash-sensor
--- Executing: flash-sensor.lua, args ''
----------------------------------------
----------------------------------------

Unlock tag
received 3 octets          
00 78 F0           
Write flag
OK          
OK          
OK          
OK          
Lock tag
received 3 octets          
00 78 F0           

-----Finished
```

That's what we write:

```
echo "Medical Lab: http://10.210.17.66:21345 id:pico pwd:19990401 " | hexdump -C
00000000  4d 65 64 69 63 61 6c 20  4c 61 62 3a 20 68 74 74  |Medical Lab: htt|
00000010  70 3a 2f 2f 31 30 2e 32  31 30 2e 31 37 2e 36 36  |p://10.210.17.66|
00000020  3a 32 31 33 34 35 20 69  64 3a 70 69 63 6f 20 70  |:21345 id:pico p|
00000030  77 64 3a 31 39 39 39 30  34 30 31 20 0a           |wd:19990401 .|
0000003d
```
