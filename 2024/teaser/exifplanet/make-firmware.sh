#!/bin/bash

echo "Unzipping..."
rm -rf ./mc021
unzip ./original_files/mc021_motor_controller_firmware_0345.zip -d ./mc021

echo "Patching the zip..."
cat ./mc021/MC021_Ver0345.mcf flag_header FLAG next_level > ./mc021/MC021_Ver0356.mcf
head -c 100 /dev/urandom >> ./mc021/MC021_Ver0356.mcf
rm -f ./mc021/MC021_Ver0345.mcf

echo "Zipping..."
zip ./tubble-discord-bot/mc021_motor_controller_firmware_0356.zip ./mc021/*

echo "Done"
