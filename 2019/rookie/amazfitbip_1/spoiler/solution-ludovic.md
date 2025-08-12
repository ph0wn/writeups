The flag is shown at the top right of the watchface provided as a bin file.
This bin file can either be flashed to the watch, or reversed

# First solution: Installing the watchface

To do so, proceed as follows:
- Install gadgetbridge in Android
- Connect by BT to the watch in GadgetBridge
- Put the watchface on the phone via adb, e.g.

adb push amazfitbip1.bin /storage/self/primary/Download

- With the phone, click on this file in a file explorer. It should open GadgetBridge and flash the new watchface


#Second solution: Reversing the firmware

Proceed as follows (Windows users)
- Install https://bitbucket.org/valeronm/amazfitbiptools/downloads/
- Drag and drop the .bin file on the executable icon (WatchFace.exe)
- Go in the created dir, open 0000.png

Process as follows (Linux and MacOS users)
- use the following python tool to extract all files from the .bin file:
https://github.com/amazfitbip/tools
- Once you have unpacked the file, the watchface is in 0000.png: it contains the flag that you be readable when you open the file

# Reset watch
Push another watchface to the watch
- Connect using GadgetBridge to the watch via BT
- Go to file manager, select another watchface: defaultwatchface.bin
- Flash it to the watchface by clicking on the file


#Factory reset the watch
- Menu of the watchface: go on the right, until settings, then go donw one screen: "Factory reset". Beware: you must be connected in MITFIT and then you have to connect via BT to the watch.
Mot de passe pour la montre: "c4challenges"

# If the watch is bricked
- plug in the charger, then short click on the button of the watch, then try to connect by BT e.g. with gadgetbridge and try to reflash a firmware.
