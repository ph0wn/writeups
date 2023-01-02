# MI5 Stage 1

- Category: Reverse
- Author: cryptax
- Points: 282
- Solves: 7

# Description

MI5, United Kingdom's domestic counter-intelligence and security agency, has developed a secure vault. This highly secured vault is used by VIPs, such as HRH Queen Elizabeth II, and MI6 secret agents to store top secret information.

Unfortunately, MI5 has recently learned the device has been hacked by a proeminent hacker group, Wasabi, to store their own secrets!!! Immediately, MI5 contacted Pico le Croco, the famous hacker of Monaco to sort this out.

Please help Pico and find the flag!

Flag information:

- There are 2 flags to recover: stage 1 and stage 2. When seeing stage 1 flag, you'll have no doubt it is for stage 1.
- Stage 1 is easier than Stage 2. But you can flag in any order you wish.

Device information:

-    https://docs.m5stack.com/en/core/coreink
-    Borrow a M5 device from organizers
-    Please be very gentle on buttons on the device or they'll break...
-    This is not a hardware challenge: do not open / tear down the device.
-    Please do not flash the device with another firmware. If you do so unintentionally or if the device seems corrupt, let us know so we can re-flash it.

# To do it without the device

Use `./files/flash_4M.bin`

# Solution

Dump the flash (it typically appears on `/dev/ttyUSB0` or `/dev/ttyACM0`):

```
esptool.py -b 921600 --port /dev/ttyUSB0 read_flash 0x00000 0x400000 ./to-reverse/flash_4M.bin
```


Install the tools. Note that the following fork works well whereas the initial one fails.

```
git clone https://github.com/yawor/esp32_image_parser
cd esp32_image_parser/
git checkout app_image
python3 -m venv venv
source ./venv/bin/activate
pip3 install -r ./requirements.txt
```

Dumping NVS:

```
./esp32_image_parser.py dump_nvs ../to-reverse/flash_4M.bin -partition nvs > ../to-reverse/dump.nvs
```

Analysis of the NVS reveals stage 1 flag:

```
$ $ grep -i ph0wn dump.nvs 
      Key : ph0wn-challenge
          NS : ph0wn-challenge
          NS : ph0wn-challenge
          NS : ph0wn-challenge
        Data : ph0wn{stage1_read_the_nvs}
          NS : ph0wn-challenge
          NS : ph0wn-challenge
          NS : ph0wn-challenge
```


Flag: `ph0wn{stage1_read_the_nvs}`