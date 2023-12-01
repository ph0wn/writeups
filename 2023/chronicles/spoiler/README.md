Alternate solution from @Geluchat: https://x.com/Geluchat/status/1729551963455365433?s=20

1. Reverse the Scenario Editor
2. Parse the scenario file with the following script to get direct info:

```python
#!/usr/bin/python2.7

def xor_strings(s,t):
	return "".join(chr(ord(a)^ord(b)) for a,b in zip(s,t))

f = open('missing-caviar.communityscenario').read()
key = ''.join([ chr(103), chr(107), chr(237), chr(157), chr(183), chr(50), chr(221), chr(156) ]);
open('cheese.scenario','w').write(xor_strings(f[0x15:],(key*1000000)[:len(f)]))
```

