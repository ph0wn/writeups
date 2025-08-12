#!/bin/sh

cd `dirname "$0"`
qemu-system-aarch64 \
    -nographic \
    -smp 2 \
    -machine virt,secure=on -cpu cortex-a57 \
    -d unimp -semihosting-config enable,target=native \
    -m 1057 \
    -bios bl1.bin \
    -initrd rootfs.cpio.gz \
    -kernel Image -no-acpi \
    -monitor null -serial stdio
