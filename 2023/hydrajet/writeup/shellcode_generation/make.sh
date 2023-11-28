#!/bin/bash

arm-none-eabi-gcc -c -mthumb -mcpu=cortex-m3 -o dumpcode.o dumpcode.S
arm-none-eabi-objcopy -O binary -j .text dumpcode.o dumpcode.bin

arm-none-eabi-gcc -c -mthumb -mcpu=cortex-m3 -o printfcode.o printfcode.S
arm-none-eabi-objcopy -O binary -j .text printfcode.o printfcode.bin


arm-none-eabi-objdump -d dumpcode.o
hexdump -C dumpcode.bin

arm-none-eabi-objdump -d printfcode.o
hexdump -C printfcode.bin

