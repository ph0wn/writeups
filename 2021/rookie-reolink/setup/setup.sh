#!/bin/bash
ORIGINAL_FIRMWARE=IPC_515B16M5M.136_20121102.RLC-410W-5MP.OV05A10.5MP.WIFI1021.REOLINK.IPC_515B16M5M

echo "Clean up..."
rm -f fs.squash
cp ../firmware/$ORIGINAL_FIRMWARE ./patched.firmware

echo "Removing the original Squash FS"
truncate -s 5164371 ./patched.firmware

echo "Creating SquashFS..."
mksquashfs ./squashfs-root/ fs.squash

echo "Patching the firmware..."
dd if=./fs.squash of=./patched.firmware seek=4919 conv=notrunc bs=1

echo "Testing with binwalk..."
binwalk ./patched.firmware | grep "0x1337"

echo "SHA256 of firmware"
cp ./patched.firmware ../public/camera-firmware
ls -lh .
sha256sum ./patched.firmware


