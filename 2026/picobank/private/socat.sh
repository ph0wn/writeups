#!/bin/bash

stty -F /dev/ttyACM0 raw -echo -echoe -echok -echoctl -echoke -ignbrk -onlcr
stty -F /dev/ttyACM0 115200 raw -echo -echoe -echok -echoctl -echoke

socat -v -x TCP-LISTEN:1337,reuseaddr,fork GOPEN:/dev/ttyACM0,b115200,rawer,echo=0,clocal=1,hupcl=0

