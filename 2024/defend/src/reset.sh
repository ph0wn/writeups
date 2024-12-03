#!/bin/bash

# Default serial port (Update based on your OS)
PORT="/dev/ttyUSB0"

# Baud rate (Adjust if necessary)
BAUD_RATE=1500000

# Flash address for writing (Default for ESP32)
FLASH_ADDRESS=0x0000

# Size to read (16MB for Core2, 8MB for Stick C Plus2, 4MB for Stick C and Stick C Plus)
FLASH_SIZE=0x400000

# Default file name for both flashing and reading
FIRMWARE_FILE="evcharger.0.12.bin"
DUMP_FILE="dump.bin"

# Function to show usage
usage() {
    echo "Usage: $0 [-f] [-r] [-p <serial_port>] [-b <baud_rate>] [-a <flash_address>] [-s <flash_size>]"
    echo "  -f                   Flash firmware from file $FIRMWARE_FILE to the device"
    echo "  -r                   Read flash of the device and write to $DUMP_FILE"
    echo "  -p <serial_port>     The serial port where the device is connected (Default: $PORT)"
    echo "  -b <baud_rate>       Baud rate for flashing (Default: $BAUD_RATE)"
    echo "  -a <flash_address>   Flash address for writing (Default: $FLASH_ADDRESS)"
    echo "  -s <flash_size>      Flash size to read (Default: $FLASH_SIZE)"
    exit 1
}

# Parse command line options
while getopts ":frp:b:a:s:" opt; do
    case ${opt} in
	f) FLASH_OPTION=1 ;; 
	r) DUMP_OPTION=1 ;;
        p) PORT=${OPTARG} ;;
        b) BAUD_RATE=${OPTARG} ;;
        a) FLASH_ADDRESS=${OPTARG} ;;
        s) FLASH_SIZE=${OPTARG} ;;
        *) usage ;;
    esac
done

# Check if esptool.py is installed
if ! command -v esptool.py &> /dev/null; then
    echo "Error: esptool.py not found. Install it using 'pip install esptool'"
    exit 1
fi

# Check for both options or none
if [ ! -z "${FLASH_OPTION}" ] && [ ! -z "${DUMP_OPTION}" ]; then
    echo "Error: Specify either -f (flash) or -r (read) but not both."
    usage
fi

if [ -z "${FLASH_OPTION}" ] && [ -z "${DUMP_OPTION}" ]; then
    echo "Error: You must specify either -f (flash) or -r (read)."
    usage
fi

# Check if the user wants to read the flash
if [ ! -z "${DUMP_OPTION}" ]; then
    echo "Downloading flash memory to file: ${DUMP_FILE}"
    esptool.py --chip esp32 --port ${PORT} --baud ${BAUD_RATE} read_flash 0x00000 ${FLASH_SIZE} ${DUMP_FILE}

    # Check if the read was successful
    if [ $? -eq 0 ]; then
        echo "Flash memory successfully downloaded to ${DUMP_FILE}"
    else
        echo "Failed to download flash memory!"
    fi
    exit 0
fi

# Check if the user wants to flash the device
if [ ! -z "${FLASH_OPTION}" ]; then
    # Erase flash memory before flashing
    echo "Erasing flash memory..."
    esptool.py --chip esp32 --port ${PORT} --baud ${BAUD_RATE} erase_flash

    # Check if the erase was successful
    if [ $? -ne 0 ]; then
        echo "Flash erase failed!"
        exit 1
    fi

    # Flash the device
    echo "Flashing M5 Stick with firmware: ${FIRMWARE_FILE}"
    esptool.py --chip esp32 --port ${PORT} --baud ${BAUD_RATE} write_flash ${FLASH_ADDRESS} ${FIRMWARE_FILE}

    # Check if the flash was successful
    if [ $? -eq 0 ]; then
        echo "Flashing successful!"
    else
        echo "Flashing failed!"
    fi
    exit 0
fi
