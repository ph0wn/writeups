# Stage 1

Connecting to the device and sending a `ctrl-c` gives the user access to the
python console. then, using the following code, the contestant can read the
available files :

```
import os
os.listdir(".")
```

Loading `main.py` shows the source of the interface, which calls a builtin
module called `level1`. This module cannot be extracted directly, but can be
called from the command line. A simple bruteforce on the 6 digits code gives the
solution.

# Stage 2

While doing the first level, the contestant should have found a `level2.mpy`
file, which is a python module compiled by micropython. This module contains
frozen bytecode that should be reversed in order to retrieve the password.
To get started, the micropython source offers the `mpy_tool.py` script that
dumps frozen bytecode. Next step is to recover the missing instructions by
looking at the micropython VM source code, then reverse engineer the algorithm
to retrieve the password.

I'd add bonus points if somebody comes with a pull request for the python `xdis`
module for micropython bytecode ;)
