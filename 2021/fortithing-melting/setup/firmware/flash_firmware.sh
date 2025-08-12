port="ttyUSB0"
if [ $# -eq 1 ]; then
	port="$1"
fi

esptool.py --port /dev/$port erase_flash
esptool.py --port /dev/$port write_flash -fm dio --flash_size=detect 0 ./firmware/micropython/ports/esp8266/build-GENERIC/firmware-combined.bin
