
# Megademo

## Solution

### Level 1

Genre: Forensic
The flag is redacted from the firmware. But ``strings ph0wn{`` brings you the flag.
The flag is present in an hidden HTML page.
Just after the flag, the HTML end page tags let place to an other HTML page starting with it's name.
This means you need to go back at the beginning of the current page and find the page's name.
Then, just display it (``http://192.168.0.90/h1dd3n.cgi``)

### Level 2

Genre: Exploit
Again, the flag is visible in the firmware but redacted too.
The goal is to dump it from the firmware or the RAM (the flash is copyed from flash to RAM to defeat optimisation from compiling stage).
The current page is a CGI, as the first one called form index.html. But no parameters as we seen before on the first level.
You need to reverse the firmware to find those parameters. A clever use of ``strings firmware.axf`` will give you ALL. You just need have a look and play with XREF to find relevant part of the code of each parameter.
When the parameters are understood the hardest things is to know how to display the flag.
Parameter "debug" set the value to be displayed on the h1dd3n.cgi page, in the last HTML line. Playing with "pokeAdr" and "pokeValue" will give you the opportunity to leek 4 bytes by 4 bytes the flag.

Important addresses:        
The CGI parameters of the page h1dd3n.cgi are handled at ``0x60003130``.      
The ``debug`` parameter is interesting, it set how the ``h1dden.shtml`` page will display the last parameter.         
The case ``1`` and ``2`` are the way to display 4 bytes from a pointer. It displays ``*madpointer``.        
The ``madpointer`` is located in ``0x2000316c``.        
The flag is located in ``0x20003170`` or ``0x60019b38``.      
Continuing reversing the ``h1dd3n.cgi`` page, you can found 2 other parameters ``pokeAdr`` and ``pokeValue``.      
So:      
Calling the CGI with http://192.168.0.90/h1dd3n.cgi?debug=1 or http://192.168.0.90/h1dd3n.cgi?debug=2 will set the ``h1dd3n.shml`` in the good mode for displaying 4 bytes at address located in ``0x2000316c``.       
Then calling the CGI with http://192.168.0.90/h1dd3n.cgi?debug=1&pokeAdr=0x2000316c&pokeValue=0x20003170 will display the 4 first byte of the flag.
Then http://192.168.0.90/h1dd3n.cgi?debug=1&pokeAdr=0x2000316c&pokeValue=0x20003174 the 4 next bytes, etc.        
