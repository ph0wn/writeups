#!/bin/bash


# setup serial port
stty -F $SerPort evenp 1200

# wait for 1 second
sleep 1

# echo off
#python3 -c 'print("\x1B\x3B\x60\x58\x52")' > $SerPort

# wait for 1 second
sleep 1

# bridge Minitel to MiniPavi
socat -T 70 file:$SerPort,echo=0,b1200,raw TCP4:127.0.0.1:2323
