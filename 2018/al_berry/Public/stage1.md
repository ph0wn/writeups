# Al Berry level 1

Category: Exploitation, IOT, ARM
Author: Phil (@PagetPhil)
Points: 500 - 25 - 50
Difficulty: easy --> intermediate
Tags: no-equipment, intermediate

Our new hardened alarm with a Raspberry Pi (Al Berry) prototype is ready for a first test. 
This alarm uses a Raspberry Pi and a LCD cap to display useful things in a use friendly form. All the management goes thru a TCP/IP session.
You can access to the alarm on IP `10.210.17.68` TCP port `12345`.

Your first mission is to check if you can disarm the alarm without knowing the password management. Use the given binary to check the security in depth.
Who knows, maybe the developer have done a big mess with the code ...

Note:

- You can come and see the device at the organizer's desk
- This challenge is an ARM exploitation and relies on a Raspberry Pi. No brute-force or SSH tricks is needed to solve it. It is impossible to implement an anti-DOS on a simple RPi board, respect it for the pleasure of all participants.
- The flag has the usual format.

sha256 alberry:
`0cf316a73ee6e155f1a9703f736beed2c6d3f0a22558cb334cb73f4cbbd6e255`
