#!/bin/bash

DFU_UTIL="../dfu-util"

if [ "$EUID" -ne 0 ]
  then echo "Please run as root"
  exit
fi

${DFU_UTIL} -i 0 -a 0 -d 0483:df11 -D hydrafw.dfu
