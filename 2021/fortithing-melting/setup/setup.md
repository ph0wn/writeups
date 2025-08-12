# Network setup

We need a WiFi with:

- SSID: `fortithing-v1`
- Protocol: WPA or WPA2
- Password: `boardcreatedbythorsten`
- IP addresses connecting to that WiFi will get addresses in `10.210.17.128/27`. That IP address redirects to Google Instance `ph0wn-challenges`.
- DNS: `10.210.17.129/27`
- Gateway: `10.210.17.129/27`

# PHP server

It needs to be accessible to the WiFi above.

- IP address: `10.210.17.158`
- Port: `8080`

The server is dockerized. Go to `./server`, then `docker-compose up -d`
http://geekyplatypus.com/dockerise-your-php-application-with-nginx-and-php7-fpm/

# Boards

- FortiThing v1.0 rev C boards
- Solder an OLED screen on them

| Screen | Board |
| ---------- | -------- |
| GND | GND |
| VCC | VCC |
| SCL | SCL |
| SDA | SDA |

# Pre-built firmware

Firmware has been pre-built for the boards with the settings above. The boards can be flashed with that if there is no modification.

```
esptool.py --port /dev/ttyUSB0 erase_flash
esptool.py --port /dev/ttyUSB0 write_flash -fm dio --flash_size=detect 0 ./firmware-combined.bin
```

Then, add external modules (char_probability is not used for this challenge):

```
cd external_modules
ampy -p /dev/ttyUSB0 put main.py
ampy -p /dev/ttyUSB0 put switch.py
ampy -p /dev/ttyUSB0 put char_probability.txt
```


# Building the firmware

Please refer to students' doc `Report.pdf`

- if `python-serial` does not exist for your compile host, use `python3-serial` package.
- don't forget to put xtensa-lx106-elf binaries in your PATH, e.g: `.... /home/axelle/git/students/sp-fortithing/firmware/esp-open-sdk/xtensa-lx106-elf/bin/`

For Ph0wn, we need to adapt WiFi settings:

- copy (or adjust) `./credentials.py` to `$FORTITHING/firmware/frozen_modules/credentials.py`
- copy (or adjust) `./server.py` to `$FORTITHING/firmware/frozen_modules/server.py`

# Testing with different config

Connect with, for example, `picocom /dev/ttyUSB0 -b115200`

Then, once inside the prompt:

```python
import wifi
wlan  = wifi.connect(ssid, pw)
```
To disconnect: `wlan.disconnect()`. Is it connected: `wlan.isconnected()`.

Get default credentials:

```python
import credentials
ssid, pw = credentials.get()
```

Get default server address (internal):

```python
import server
serv = server.get()
```

Test connection with a remote server:
```
import bias
bias.get('192.168.0.42')
```

