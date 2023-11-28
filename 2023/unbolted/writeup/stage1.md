# Unbolted 1 by Le Barbier

This challenge was created by *Le Barbier*.

## Description

The description of the challenge gives us a beautiful shakespearian poem:

```
In fair Wooku Manor, where passions thrive,
Pico le Croco yearns for his love to arrive.
As Romeo sought Juliet in days of yore,
Locked doors hinder Pico, this he deplores.

To see his Juliet, his heart's desire,
A locked door burns with an unquenched fire.
To dump the memory, he knows he must dare,
Unlocking pathways to reach her fair.

"O Juliet, your absence leaves me torn,
Locked doors keep us distanced and forlorn.
To dump the memory, this task I pursue,
Unlocking barriers, to be with you true."

Like Romeo's love, persistent and strong,
Pico seeks Juliet, though fate does him wrong.
Through locked doors and memory's plight,
Love's perseverance shall grant them the light.
```

There is 1 hint, which doesn't belong to a Shakesperian world: "Dump the memory".

The device to borrow is an insecure lock:

![](./images/d89ac3e5bb45eb87402a90fc30263259.png){ width=50% }

## Step 1: Identify electronic components

The very first step is to know what you have in front of you. 
Take the lock and achieve the two next steps:

- Read the components reference/name written on the top of them
- Find the datasheets of each component on the Internet.

**Solution:**

Already done ? With some electronic devices you will not be lucky as here. 
Sometimes, components are covered with "protections" like glue or with 
metalic plate. Some old components references may be unreadable too...

In these specific cases heat the glue if any and remove it properly by scratching. You can also use a camera to take a picture of the component and be able to analyse the result with the help of your computer. (zoom, etc)

This is the list of the **interesting** components:

```
    STM32F103RBT6 : ARM microcontroller
    25LC080 : SPI memory
    24LC64 : I2C memory
    VP235 : CAN transceiver (unused at present)
```

You can easily find their datasheets with a quick search of the reference in your favorite search engine. For example, [24LC64 I2C memory datasheet](https://ww1.microchip.com/downloads/en/DeviceDoc/21189R.pdf).

### Step 2: Find the headers linked to a chip

**This part requires a multimeter**

First the explanations : 
Instead of soldering wires directly to the chip's pin that interrest you, you will sometimes be lucky and find connectors (headers) left in place that allow you to connect a wire directly without any soldering iron. 
The question is: How to detect them ? 
Ever played "Operation" before ? If yes, what follows is the complete opposite.

!! The continuity test have to be done without power on the board !!

Let's see quickly how a multimeter works: 
When you use your multimeter in "diode mode", you have two probes, place the first on the chip pin you want to test and with the other one check every available header. 
If your multimeter `beep`, there is continuity ! Theese two are connected together !

![Multimeter in diode mode](./images/3e9fca0befb0255f7df91541e333ca99.png){ width=50% }
![Find the connections with a multimeter](./images/3b24f1327836d85fa2ba42ae42257dac.png){ width=40% }

Your turn now, make a list of the headers and identify to which component  
and wich particular pin it is connected. You can use the datasheet to help you  
identify the pin name.

**Solution**

First header (from left to right) :

| n° header | Pin name | Chip |
| --- | --- | --- |
| 1   | GND | /   |
| 2   | RESET | /   |
| 3   | JTDO | STM32F103RBT6 |
| 4   | JTCK/SWCLK | STM32F103RBT6 |
| 5   | JTMS/SWDIO | STM32F103RBT6 |
| 6   | JTDI | STM32F103RBT6 |
| 7   | JNTRST | STM32F103RBT6 |
| 8   | 3V3 | /   |

Second header (from top to bottom) :

| n° header | Pin name | Chip |
| --- | --- | --- |
| 1   | Boot0 | /   |
| 2   | 3V3 | /   |
| 3   | GND | /   |
| 4   | I2C SDA | 24LC64 |
| 5   | I2C SCL | 24LC64 |
| 6   | UART RX | STM32F103RBT6 |
| 7   | UART TX | STM32F103RBT6 |
| 8   | CANHL | VP235 |
| 9   | CANH | VP235 |
| 10  | GND | /   |
| 11  | SPI CS2 | 25LC080 |
| 12  | SPI MOSI | 25LC080 |
| 13  | SPI CLK | 25LC080 |
| 14  | SPI MISO | 25LC080 |
| 15  | SPI CS1 / SPI WP | 25LC080 |

![Summary of pins](./images/c917910fbfdd795a24db4927ca590fff.png){ width=60% }

You are now able to draw a big part of the schematic of the training board.

### Step 3: Dump I2C memories

**This part requires a bus pirate or a Hydrabus or equivalent**

I2C stands for (Inter Integrated Circuit) -> synchronous communication with  
2 wires :

- SCL (Clock)
- SDA (Data)

I2C is a data bus with multi-master and multi-slave (bidirectional half-duplex). To communicate from a device to another, you have to send an address (address of the device). 
Protocol : Start bit, 7 bits address, Read or Write bit, data, ACK, Stop bit

**Solution**

Using an Hydrabus in it's default I2C configuration, you will be able to use the command **scan** to find the different I2C memories addresses.

![Finding the addresses of the memories with a Hydrabus](./images/9ee2544146179c56d709342c970842eb.png)

Similarly, with the [*Hardsploit* board](https://hardsploit.io), you can automatically scan the various memories via its graphical interface.

![Scanning memories with Hardsploit](./images/1f8a53c67f346443dceb7985b313d6d9.png){ width=70% }

| Memory | Reading address | Writing address |
| --- | --- | --- |
| I2C MEMORY N°1 | A7  | A6  |
| I2C MEMORY N°2 | AF  | AE  |

The same GUI can then be used to request extraction of the entire content of each memory.

![Full read of I2C memory with Hardsploit](./images/3c86ac2fce7541c30a13b5f415d06a7c.png){ width=70% }

![Memory dumped](.//images/1d454c1e7d2523c434686e625edaceba.png){ width=70% }

Once the file (A6-A7 memory) has been extracted, its content can be displayed using the strings command. As the string appears to be base64 encoded (`cGgwd257UzNjVXIzXzNuY1J5cHQzZF9JMkNfcDRzU3cwcmR9`), all that remains is to use the associated command to display the flag: `ph0wn{S3cUr3_3ncRypt3d_I2C_p4sSw0rd}`.

![Strings of the memory](./images/57d7131bf8d6b0f485cfb2c5a90a0eb6.png){ width=70% }

