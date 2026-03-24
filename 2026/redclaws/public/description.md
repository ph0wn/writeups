Pico le Croco is planning to cruise around Corsica. No way he goes anywhere with decent stock of food onboard (caviar, champagne, lobster etc).
This morning, Pico panicked when he received a threat from the *Red Claw Revolt* APT group (see attached email).

Your mission, should you accept it, is to secure Pico's *Yacht Food Stock*.

The service is running on a host which is protected by a **FortiWeb** device.
There is no time to fix the Yacht Food Stock's implementation.
We want you to protect the vulnerable service using the FortiWeb.
Identify the vulnerability(ies) and configure the FortiWeb so that they cannot be exploited. 
**The food stock must remain operational**.

Once you have secured Pico's food stock, come to the organizer's desk: we'll test, and if you succeed, we'll give you your flag. In case of strong demand, we may have to limit the number of attempts. **Only come for a test when you are positive you have fixed the issue(s).**

Out of Scope:

- You do *not* have access to the host running the food stock. This is *intentional*, and it's not part of the challenge. You may use the service, but may *not* modify it nor the host's configuration. 
- Only ports 80, 11002 and 13002 are in the scope of this challenge. Other ports exist, but they won't help you (except to ruin your instance). No need to do an `nmap`. **Consider all other ports as out of scope** for the challenge.
- We know you love to pwn everything, but you won't get a flag for pwning FortiWeb ;) You get a flag if we can use the Food Stock Service, but no longer exploit its vulnerabilities. Focus!


