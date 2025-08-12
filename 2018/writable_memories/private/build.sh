pip install mbed-cli
wget https://developer.arm.com/-/media/Files/downloads/gnu-rm/7-2018q2/gcc-arm-none-eabi-7-2018-q2-update-linux.tar.bz2
tar -xvf ./gcc-arm-none-eabi-7-2018-q2-update-linux.tar.bz2
mbed-cli new ./writeable_memories --scm none
cd writeable_memories
mbed-cli config GCC_ARM_PATH $PWD/gcc-arm-none-eabi-7-2018-q2-update/bin
ln -sf ./NRF51822.ld ./mbed-os/targets/TARGET_NORDIC/TARGET_NRF5x/TARGET_NRF51/TARGET_MCU_NRF51822_UNIFIED/device/TOOLCHAIN_GCC_ARM/TARGET_MCU_NORDIC_32K/NRF51822.ld
mbed-cli compile -v -t GCC_ARM -m NRF51_DK
