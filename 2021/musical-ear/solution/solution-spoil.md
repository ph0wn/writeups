Possible path:

- Install the APK
- adb logcat
- cheat for the notes (add /data/data/ph0wn.ctf.playfrequency/files/cheatfile)
- see the app is calling functions such as hint1 etc
- decompile the app
- understand it is loading another DEX which is read from its own dex but backwards
- decompile it
- find the obfuscated functions
- decrypt the strings
- spot the secretFlag function
- understand it works with a key based on notes (so A-F), see that we expect a key of length 8 and no sharp.
- bruteforce the encrypted flag and find it


