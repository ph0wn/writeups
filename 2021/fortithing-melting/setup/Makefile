all:
	make rebuild
	make flash PORT=$(PORT)
	sleep 5
	make load PORT=$(PORT)
build:
	./firmware/build_firmware.sh
rebuild:
	./firmware/build_firmware.sh rebuild
esp32:
	./firmware/build_esp32.sh rebuild
flash:
	./firmware/flash_firmware.sh $(PORT)
load:
	./firmware/put_modules.sh $(PORT)

