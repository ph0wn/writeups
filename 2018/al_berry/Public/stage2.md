# Al Berry level 2

Category: Exploitation, IOT, ARM
Author: Phil (@PagetPhil)
Points: 500 - 25 - 50
Difficulty: hard
Tags: no-equipment, hard

This challenge shares the same settings as **Al Berry Level 1**, and we strongly recommend you complete level 1 first.

If you’re here, this mean our security control has a serious flaw. But we’ve tried to protect the running RPi from unwanted access. The system is running with ASLR level 2, binary alberry is using `mprotect()` to enforce memory privileges, so you should not have any way to spawn a shell. In fact, we hope so... Someone told us about a strong high level tech called “ROP” can break everything but we’re not aware of it. 


