# sudo apt-get install libc6-armel-cross libc6-dev-armel-cross binutils-arm-linux-gnueabi libncurses5-dev gcc-arm-linux-gnueabihf g++-arm-linux-gnueabihf
break: break.c
	arm-linux-gnueabihf-gcc break.c -o break -static

# sudo apt-get install qemu-user-static
run: break
	qemu-arm-static ./break

clean:
	rm -f break
