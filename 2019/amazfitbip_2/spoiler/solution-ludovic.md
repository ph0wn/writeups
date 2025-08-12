The flag is progressively shown just next to Mario when the number of steps increases.
This bin file provided to teams can either be flashed to the watch, or reversed

# First solution: Installing the watchface

To do so, proceed as follows:
- Install gadgetbridge in Android
- Connect by BT to the watch in GadgetBridge
- Put the watchface on the phone via adb, e.g.

adb push amazfitbip2.bin /storage/self/primary/Download

- With the phone, click on this file in a file explorer. It should open GadgetBridge and flash the new watchface

- Then, change in e.g. gadgetbridge the number of objective steps per day, e.g. to 10
-Walk for 10 steps. You should see all the letters of the flag displayed on the screen


#Second solution: Reversing the firmware

Proceed as follows (Windows users)
- Install https://bitbucket.org/valeronm/amazfitbiptools/downloads/
- Drag and drop the .bin file on the executable icon (WatchFace.exe)
- Go in the created dir, open 0040.png to 0041.png. You know that these are this files by having a look at donkey.json, section "StepsProgress"

Process as follows (Linux and MacOS users)
- use the following python tool to extract all files from the .bin file:
https://github.com/amazfitbip/tools
- Once you have unpacked the file, see the solutions for Windows users, step 3.

# Reset watch
Push another watchface to the watch
- Connect using GadgetBridge to the watch via BT
- Go to file manager, select another watchface: defaultwatchface.bin
- Flash it to the watchface by clicking on the file
- Reset the daily step objective to "10000" in gadgetbridge: menu on the top left, then "settings", then "Device specific settings"


#Factory reset the watch
- Menu of the watchface: go on the right, until settings, then go donw one screen: "Factory reset". Beware: you must be connected in MITFIT and then you have to connect via BT to the watch.
Mot de passe pour la montre: "c4challenges"
