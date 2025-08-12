# Track the Hacker

Title: Track the Hacker
Category: OT
Author: m0eukh
Tags: no-equipment, intermediate

*This challenge shares the same environment as `Save The Factory`. The scenario suggests you save the factory first, and then track the hacker. But, actually, you can solve them in the order you wish.*

*FearFactory* is an industrial factory whose sensitive units are supervised and controlled by a **main board**.
The communication between the **main board** and the rest of the physical equipment (machines, sensors, ...) is done using the **OPC-UA protocol**.

Due to a misconfiguration of access rights, a **smart hacker managed to gain access to the server and mess it up**.

**S/he found a tricky way to store some data (probably a file, or something for later use?) in the tree graph of the OPC-UA server**. It seems s/he had to stop what s/he was doing abruptly and didn't have enough time to clean up his/her traces.

Can you **retrieve the data** s/he stored?

The factory credentials and shell access are the same as in `Save The Factory`.
