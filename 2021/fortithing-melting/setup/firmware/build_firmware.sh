rebuild="rebuild"
if [ $# -eq 1 ]
then
	if [ "$1" = "$rebuild" ]
	then
		rm -rf ./firmware/micropython/ports/esp8266/modules/*
		make -C ./firmware/micropython/ports/esp8266/ clean
	fi
fi
cp -a ./firmware/frozen_modules/. ./firmware/micropython/ports/esp8266/modules/
make -C ./firmware/micropython/ports/esp8266/

