# Setup

- Bring the ReconJet glasses
- Bring the USB cable
- Print `hidethis-stage3.png` and hide it NW of the room. On that sheet, mark top column left is A to T. And Top row is 1 down to 20.
- Print a sheet to book the glasses for a given time
- Bring tape to secure the glasses (one branch often falls off)

## Between each team

ph0wn.apk: sha256: 
`551062235ab35e27e373b184464e97c804e02de8283d4b1d0d15b28434c0d555`

1. The ReconJet must be powered on and booted.
2. Remove the app: `adb uninstall ph0wn.reconjet`
3. Re-install the app: `adb install ph0wn.apk`
4. Look in /sdcard and erase what's not meant to be there

```bash
shell@android:/sdcard $ ls
Alarms
DCIM
Download
Movies
Music
Notifications
Pictures
Podcasts
ReconApps
Ringtones
log.txt
tmp
```

### Connecting to the glasses from a terminal


On the glasses:

- Ensure that the glasses have booted (white light)
- Ensure that USB debugging is enabled on the glasses (Settings, Advanced)

On the laptop:

`sudo apt install android-tools-adb`

Create or edit this file `~/.android/adb_usb.ini`:

```
# ANDROID 3RD PARTY USB VENDOR ID LIST -- DO NOT EDIT.
# USE 'android update adb' TO GENERATE.
# 1 USB VENDOR ID PER LINE.
0x2523
```

Then

```bash
adb kill-server
adb start-server
adb devices
```


## Troubleshooting

If connection does not work, try adding this /etc/udev/rules.d/99-adb.rules:

```
SUBSYSTEM=="usb", ATTR{idVendor}=="2523", ATTR{idProduct}=="d209", OWNER
```


# How I created the challenge

## Stage 1

I generated [the QR code using this online generator](http://fr.qr-code-generator.com/)

It says: `The flag is: Ph0wn{ScottWishesHeHadOurSmartGlasses} Stage 2 key: X@M`.

The stage 2 key is here to make it more difficult to flag stage 2 without flagging stage 1.

Then, I created an Android application that displays that QR code.
The QR code image is in the assets of the application.
So that it displays entirely on the screen, I limited its display size to 150 pixels in the main.xml layout.

To create the Android project, I am still using the old ANT mechanisms:

```
android create project --package ph0wn.reconjet --activity MainActivity --target 2 --path ./app
```

## Stage 2

I created another activity which is never called.
I added it to the Android Manifest.
The tag `android:exported="true"` is important for the activity to be displayable via `adb shell am start`.

I wanted the text to be scrollable so that people could solve the challenge without having to reverse the application.
This is done by `txtView.setMovementMethod(new ScrollingMovementMethod());` in the activity and adding maxLines and scrollbars to the layout of hidden:

```xml
<TextView
  android:layout_width="fill_parent" 
    android:layout_height="wrap_content" 
    android:id="@+id/txtView"
    android:maxLines = "9"
    android:scrollbars = "vertical"
    />
```

Also, I didn't want people to find the text with a simple `strings` command on `classes.dex`. So, I passed the int (ASCII) value of each character. I wrote a quick Python script to do that:

```python
# to encode
text='the text to encode'
tab = [ ord(text[i]) for i in range(0, len(text)) ]
print tab

# to decode
decode = [ chr(x) for x in tab ]
print ''.join(decode)
```

## Stage 3

I generated the list of words using [this word finder generator](http://tice.avion.free.fr/fswordfind/fswordfinder.php) with:

- words: FORTINET, TELECOM, PARISTECH, PLATEFORME, CONCEPTION, EURECOM, GREHACK
- orienting words: "gauche -> droite", "jamais en diagonale", "Haut en bas / bas en haut"


## Flags

- Stage 1: `Ph0wn{ScottWishesHeHadOurSmartGlasses}`
- Stage 2: `Ph0wn{X@MPicoIsEverywhere}`
- Stage 3: `Ph0wn{c_Hqopef91M17HI10VO19VK1VL14VA8VC12V}`
