# Installing tools

- https://github.com/micropython/micropython/tree/master/ports/esp32
- or test with pre-compiled firmware: https://micropython.org/resources/firmware/esp32-idf4-20201016-unstable-v1.13-106-g893f75546.bin

I used IDF3.

To compile:

- source ~/git/students/sp-fortithing/firmware/micropython/ports/esp32/build-venv/bin/activate
- ensure PATH has `~/softs/xtensa-esp32-elf/bin`

Then:

- make esp32 from $FORTITHING root
- 

# Flashing

From $FORTITHING_ROOT:
```
esptool.py --chip esp32 --port /dev/ttyUSB0 erase_flash
cd firmware
esptool.py --port /dev/ttyUSB0 --chip esp32 write_flash -z 0x1000 ./micropython/ports/esp32/build-GENERIC/firmware.bin
```

# Adding modules

```
cd ./external_modules
ampy -p /dev/ttyUSB0 put ./main.py
ampy -p /dev/ttyUSB0 put ./switch.py
```

# Modifications to the code:

- "Warning: I2C(-1, ...) is deprecated, use SoftI2C(...) instead"

```
from machine import SoftI2C, Pin
i2c = SoftI2C(...
```

- Pins in `./external_modules/switch.py`

```
i2c = SoftI2C(scl=Pin(22), sda=Pin(21), freq=400000)
bme = bme280_float.BME280(i2c=i2c)
```

- switch: user button is on GPIO12

```
if os.uname().sysname == 'esp32':
        sw3 = Pin(12, Pin.IN, Pin.PULL_UP) # button
        #rst2 = Pin(0, Pin.IN, Pin.PULL_UP)
```

we have no rst2 button, so don't know how to do this...


>>> def calibrate():
...     t0 = time.time()
...     values = []
...     while (time.time() - t0) < 3:
...         values.append(float(bme.values[0][:-1]))
...     env_temp = sum(values)/len(values)
...     print("Sensor calibrated: " + str(env_temp))
... 
>>> calibrate()
Sensor calibrated: 27.63595
