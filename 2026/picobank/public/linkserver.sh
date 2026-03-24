#!/bin/bash

CONTAINER_NAME="picobank"

if [ ! -f LinkServer.zip ]; then
    echo "LinkServer.zip not found. Please download it from "
    exit 1
fi

if ! distrobox ls | grep -q "$CONTAINER_NAME"; then
    distrobox create --name $CONTAINER_NAME --image ubuntu:22.04 --yes
    
    distrobox enter $CONTAINER_NAME -- bash -c "
        sudo apt-get update && sudo apt-get install -y libusb-1.0-0-dev dfu-util whiptail usbutils unzip udev
        unzip -o LinkServer.zip

        chmod u+x LinkServer/LinkServer_25.12.83.linux.x86_64.deb.bin
        sudo sh LinkServer/LinkServer_25.12.83.linux.x86_64.deb.bin -- acceptLicense
        rm -rf LinkServer
    "
fi

distrobox enter $CONTAINER_NAME -- /usr/local/LinkServer_25.12.83/LinkServer "$@"
