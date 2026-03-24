SRC := src/PicoAlloc.c src/PicoAllocLinux.c src/PicoBank.c src/PicoBankManager.c src/PicoBankCmdParser.c

SRC_DEBUG := $(SRC) src/PicoAllocTests.c

all: deploy

deploy:
	cp mcuxpresso/picobank/Debug/picobank_private.bin private/
	cp mcuxpresso/picobank/DebugPublic/picobank.bin public/

picobank: $(SRC)
	clang -std=c23 -I $(PWD)/include -g -DPICO_LINUX -Wall -Werror $(SRC) -o picobank

picobank_debug: $(SRC_DEBUG)
	clang -std=c23 -fsanitize=address -I $(PWD)/include -g -DPICO_DEBUG -DPICO_LINUX -Wall -Werror $(SRC_DEBUG) -o picobank_debug

picobank_release: $(SRC)
	clang -std=c23 -I $(PWD)/include -O3 -DPICO_LINUX -Wall -Werror $(SRC) -o picobank_release

clean:
	rm -rf picobank compile_commands.json .cache picobank_debug picobank_release

.PHONY: deploy
