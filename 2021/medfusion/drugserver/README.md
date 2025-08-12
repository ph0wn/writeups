- public_drugserver: executable we can distribute, for CTF players to understand how to exploit.
- drugserver: real executable running remotely. Contains the flag. Do not distribute.

The drug server has a heap overflow, inspired from [here](https://samsclass.info/127/proj/p7-heap0.htm).

To find function addresses in GDB: `info functions`

# Refs

- https://github.com/sgayou/medfusion-4000-research/blob/master/doc/README.md#telnet-shell-analysis
