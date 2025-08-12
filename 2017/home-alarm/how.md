# How the challenge was created

## Removing logs

Log functions are removed during code obfuscation with ProGuard:

```
# remove logs
-assumenosideeffects class android.util.Log {
    public static *** d(...);
    public static *** v(...);
}
-assumenosideeffects class ph0wn.ctf.alarm.SettingsActivity {
   public static *** log(...);
   public static *** logByteArray(...);
   }
```

The Makefile also patches the `DEBUG` variable:

```
bin/SettingsActivity-release.apk:
	sed -i 's/DEBUG.=.true/DEBUG = false/g' src/ph0wn/ctf/alarm/SettingsActivity.java
	ant release
```

## Smali hack

We notice that code inserted within a packed-switch instruction is invisible to most/all? decompilers.

So, we generated some smali code to do XOR on the passphrase, and pasted that smali code inside the switch of the `sherlock` method.

To generate the code, uncomment the `passXor()` method in BadLoginException.java, and compile in debug mode (`ant debug`).

Quick test:

The application is "hacked" if when running `strings` on classes.dex you see `oNcTSFM@SR``UiNLD`

## Keystore

The ph0wn keystore was generated using

`keytool -genkey -alias ph0wn -v -keystore ph0wn.ks`


## Settings file

To generate `settings.dat`, we simply launched the Android application on an Android emulator, and ran `New Settings`, where we configured a dummy phone number, and the flag as secret code. The passphrase is to be decoded from the Smali XOR:

- Phone number: `0410203040`
- Secret: `Ph0wn{MeWantCookiesShareThemMaybe}`
- Passphrase: `NoBurglarsAtHome`


