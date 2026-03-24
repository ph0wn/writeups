PYENV_ENV = micropython-venv
PICOTOOL = $(HOME)/softs/picotool/build/picotool
MPY = $(HOME)/softs/micropython
PORT = $(MPY)/ports/rp2
MODS = $(PORT)/modules
UF2 = $(PORT)/build-RPI_PICO/firmware.uf2
LIB_NAME = HT16K33
LIB_PATH = /lib

# Ensure we are in the right pyenv
.PHONY: ensure-pyenv
ensure-pyenv:
	@if [ "$$(pyenv version-name 2>/dev/null)" != "$(PYENV_ENV)" ]; then \
		echo "Please activate venv: pyenv activate $(PYENV_ENV)"; \
	fi

all: ensure-pyenv firmware flash flash_main

firmware: $(UF2)

$(UF2): freeze/*.py
	cp freeze/*.py $(MODS)/
	$(MAKE) -C $(PORT) submodules
	$(MAKE) -C $(PORT)
	rm $(MODS)/badge_display.py $(MODS)/oled.py

flash: $(UF2)
	sudo $(PICOTOOL) load $(UF2) -f
	sudo $(PICOTOOL) reboot
	sleep 3

library:	
	@if ! mpremote fs ls $(LIB_PATH) >/dev/null 2>&1; then \
		echo "Library $(LIB_NAME) missing, installing..."; \
		mpremote mip install github:smittytone/HT16K33-Python; \
	else \
		echo "Library $(LIB_NAME) already installed"; \
	fi

flash_main: library
	mpremote fs cp main.py :
	mpremote fs cp ./lib/ssd1306.py :lib/ssd1306.py
	mpremote reset
