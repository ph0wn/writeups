deploy: arm x86
	cp exitonly ../deployment/exitonly
	cp exitonly ../public_files/exitonly
	cp flag ../deployment/flag

arm: exitonly.c
	arm-linux-gnueabihf-gcc exitonly.c -o exitonly -static
x86: exitonly.c
	gcc -o exitonly-x86 exitonly.c

arm-debug: exitonly.c
	arm-linux-gnueabihf-gcc -ggdb exitonly.c -o exitonly -static
x86-debug: exitonly.c
	gcc -o exitonly-x86 -ggdb exitonly.c
serve:
	socat TCP-LISTEN:31337,reuseaddr,fork,bind="localhost" exec:"qemu-arm ./exitonly"
serve-debug:
	socat TCP-LISTEN:31337,reuseaddr,fork,bind="localhost" exec:"qemu-arm -g 4444 ./exitonly"
serve-x86:
	socat TCP-LISTEN:31337,reuseaddr,fork,bind="localhost" exec:"./exitonly-x86"
