cd src/washingmachine
mbed compile
cd ../..


cp src/washingmachine/BUILD/NUCLEO_L152RE/GCC_ARM/washingmachine.bin ./bin/private.bin
cp src/washingmachine/BUILD/NUCLEO_L152RE/GCC_ARM/washingmachine.elf ./bin/private.elf

cp src/washingmachine/BUILD/NUCLEO_L152RE/GCC_ARM/washingmachine.bin ./bin/public.bin
cp src/washingmachine/BUILD/NUCLEO_L152RE/GCC_ARM/washingmachine.elf ./bin/public.elf

sed -i s/ph0wn{capture_me_on_the_device}/ph0wn{wtf_man\?\!_memset0_on_rom}/g ./bin/private.bin
sed -i s/ph0wn{capture_me_on_the_device}/ph0wn{wtf_man\?\!_memset0_on_rom}/g ./bin/private.elf
