# Healing the Toothbrush - Stage 2

Category: System 
Author: cryptax
Difficulty: Difficult
Points: 500 - 20 -100

**You need to solve Healing the Toothbrush 1 to do this challenge**

Now that you know how events are encrypted in my toothbrush's subconscious mind, you are ready to **have it talk**.
Fortunately, the psychiatrist helps you.

The toothbrush's subconscious mind consists,  apparently, in a rolling table of 256 *events*, where each event is indexed by its index in the table.
You cannot directly read an event entry, but you can *ask information for that index*.
For that, you need to:

- Enable notifications via handle **0x26**
- Write to handle **0x28** with data containing a **dummy event** with the index you want to *read*
- Receive notifications on handle **0x25**

The **format of an event** is:

- raw data: 5 bytes (not used)
- start date: 6 bytes (`YY MM DD HH MM SS`)
- brushing duration: 4 bytes 
- index: 1 byte

Total: **16 bytes**

Then, the bytes of the event are swapped reverse (first byte becomes last) and **encrypted** (see stage 1).


Finally, the following might help you:

- [Is my toothbrush really smart?](https://download.ernw-insight.de/troopers/tr18/slides/TR18_NGI_BR_Is-my-toothbrush-really-smart.pdf)
- **Sample Python code** to get notifications (`example.py`). This code is for Linux only. If you don't have Linux, reading the source code might nevertheless help you out.

**Important**:

- Please come and borrow the toothbrush when you need it.
- If you don't have a Bluetooth Low Energy capable device, you may also *borrow a BLE USB dongle*.
- Remember that you can **only have one connection at a time on a BLE device**.
- You are **not allowed to modify the toothbrush's hardware**.
- Sometimes, the toothbrush doesn't respond very well. We recommend you **try 2 or 3 times** :(
- Sorry, we don't provide tooth paste.
