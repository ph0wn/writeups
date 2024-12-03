The hardware for this challenge was designed by Baldanos.
The challenge was created by Cryptax.

Two challenges were uploaded on the badge:

- Stage 1 was solved 22 times, for 50 points. Soldering equipment + badge is required.
- Stage 2 was solved 11 times for 400 points. Firmware is required.

To flash stage 1 and stage 2 on the PCB, install [PICO SDK](https://github.com/raspberrypi/pico-sdk), then:

```
cd loader
mkdir build
cd build
cmake ..
make 
```

If you wish to flash only stage 2, you can use `./src/stage2/standalone-src`.

If you **don't** have the PCB, you can partially play the challenge by assuming you have downloaded the firmware `./loader/firmware.0.15.uf2`.
