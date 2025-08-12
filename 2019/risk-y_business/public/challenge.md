# RISK-Y BUSINESS

Hey, hey, here we are! Did you ever put your hands on a board with a real RISC-V MCU? No? So, this challenge is for you.

The onboard MCU is a GD32VF103CBT6 manufactured by GigaDevice, a well (not)known Chinese factory. The goal of this chip is to invade the IOT market in replacement of the STM32F103CBT6: it is pin-to-pin compatible and the memory quantities, mapping are the SAME!

So, when you’ll check the security of this fancy IOT, will you be able to run code inside and get out all it secrets? We’ll see…

The goal of this challenge is to p0wn the board by writing a shellcode and to get out the “flag” from the RAM.

As we are kind enough at ph0wn, we give you the non-stripped .elf binary running inside the MCU, the SDK to generate code, and a few RISC-V docs to accelerate the job.

How the shellcode is called:
~~~~
uint8_t shellcode[50];       
...             
/* read 50 bytes in shellcode */               
...                 
(*(void(*)()) shellcode) ();`
~~~~

The flag is computed at boot time and is available here:
~~~~
uint8_t flag[32]="ph0wn{xXxXxXxXxXxXxXxXxXxXxXxX}\0";
~~~~
                         
                        
Refer to the 2 photos for connecting to the board and find where the RESET is. The USART setup is 115200 8N1.                    

**PLEASE,** **PLEASE,** **PLEASE,** do not open the case, the boards are fragile, 2 were broken during the development. 

Good luck!

Nota: you can download some **documentation and toolchain** from our **FTP** server on **10.210.17.66**

- id: `ph0wn`
- pwd: `ph0wn2019`
