#!/bin/bash

cc -std=gnu99 -Wall -Wextra  -pedantic -O0  -c -o gpio.o gpio.c
cc -std=gnu99 -Wall -Wextra  -pedantic -O0  -c -o lcd.o lcd.c
cc -std=gnu99 -Wall -Wextra  -pedantic -O0  -c -o lcd_lowlevel.o lcd_lowlevel.c
cc -std=gnu99 -Wall -Wextra  -pedantic -O0  -c -o lcd_cgram.o lcd_cgram.c
cc -std=gnu99 -Wall -Wextra  -pedantic -O0  -c -o alberry_01.o alberry_01.c
cc -static  alberry_01.o gpio.o lcd.o lcd_lowlevel.o lcd_cgram.o -o alberry

