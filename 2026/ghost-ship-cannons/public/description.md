# Ghost Ship’s Cannons

Pico’s pirate ship has been struck by a ghost vessel! The captain’s HMI is dead, and the PLC controlling the cannons is locked. The AUTO sequence is corrupted - the sensors are unreliable - so the PLC has entered a halt: the loader is stuck and the cannons won’t fire.

A crewmate has opened a direct connection to the PLC. Your mission is to break the curse, take back control, and fire the cannons once. Doing so will overwrite the false message in the registers and reveal the flag.

- Test your sequence on `chal.ph0wn.org` port `5020`.
- Get the real flag from the PLC: connect to the PLC using a direct Ethernet cable, configure to reach the PLC at 192.168.1.4 (this is **NOT** available on ph0wn's SSID!), and run your commands to get the flag.


Flag begins with an uppercase: `Ph0wn{`.