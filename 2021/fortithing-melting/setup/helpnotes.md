source ~/softs/venv/fortithing-venv/bin/activate
sudo usermod -a -G dialout ${USER}
ampy -p /dev/ttyUSB0 ls

picocom /dev/ttyUSB0 -b115200


To rebuild the firmware, on my other laptop
export PATH=$PATH:.... pash for xtensa-lx106-elf-gcc (do a locate to get it)

Then, from git/students/sp-fortithing:

make rebuild
make flash
modify the external_modules at will
make load

