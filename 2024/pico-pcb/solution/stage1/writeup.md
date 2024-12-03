We connect the Pico PCB board on our laptop and see it is available on /dev/ttyACM0. So, we talk to it:

```
$ picocom -b 115200 /dev/ttyACM0
Pico PCB Loader v0.1...
-----------------------------
Welcome to the Pico PCB Board
Stage 1: Hardware
Stage 2: Car
Select challenge: Hardware
Hardware challenge ---------
Amnesia. Something is hidden deep down in my memory but I cant understand it.
```

The board talks about a *memory* + the Flash memory is isolated on the board.

TODO: Read the memory
TODO: Un-solder it with a hot station

Under the Flash memory, there is a QR code!

![](../screenshots/pcb-beta-sans-chips.jpeg)

*TODO*: take a picture of the de-soldered memory

We scan it and it goes to : ph0wn.org/pcb-key. 
We go to that URL and receive the following:

```
key: thanks_to_balda!
IV:  butter_soldering
```

TODO: from the memory, identify the encrypted part.
TODO: decrypt and get the flag.
