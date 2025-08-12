#!/bin/bash

echo "========= Erasing flash..."
esptool.py --port /dev/ttyUSB0 erase_flash

echo "========= Flashing challenges..."
esptool.py --port /dev/ttyUSB0 write_flash -fm dio --flash_size=detect 0 ./firmware-combined.bin

sleep 2

echo "========= Adding external modules"
cd external_modules
for entry in `ls -1`
do
  echo "Adding $entry..."  
  ampy -p /dev/ttyUSB0 put $entry
done
ampy -p /dev/ttyUSB0 put ./char_probability.txt
