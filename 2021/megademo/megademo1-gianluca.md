# ph0wn 2021 MegaDemo1 challenge

### Description

There's a board meeting at 2am. Pico le Croco needs to convince the board to give him lots of dollars. To do so, he first needs to discover a secret. Can you help him?

Important notice: Despite it embeds a HTTP server, this challenge runs on a micro-controller. Not on a dual XEON with 512GB of RAM. Please do not use DirBuster-like tools or any security scanner, it’ll not pop any flag and may crash the challenge.

Challenge is running here: http : // megademo.ph0wn.org 
Download the firmware and ... find the flag!

### First Step

I used `cat` command on `firmware.axf` and reading rapidly the output I saw a string `ph0wn{XXXXXXXXXXXXXXXX}` inside  a page named `/h1dden.shtml1

### Second Step

I first connected on the given url http :// megademo.ph0wn.org and then to http: // megademo.ph0wn.org/h1dden.shtml. Flag was at the end of the page

### Solution

Flag: `ph0wn{youFoundThePage!}`
