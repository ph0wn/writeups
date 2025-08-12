The normal Ph0wnFrontend DEX is hacked to include a Ph0wnPayload DEX.
So, we need to:

1. Compile Ph0wnFrontend and Ph0wnPayload
2. Hack Ph0wnFrontend
3. Re-sign Ph0wnFrontend

This is done by the script `prepare.sh`.

# How prepare.sh works

## Keystore creation

The `ph0wn.keystore` has been setup with:

- key alias `test`
- password `totototo` (this is not really a secure password, but we don't really care as this app is not meant to be deployed publicly)

`keytool -genkey -v -keystore ph0wn.keystore -alias test -keypass test1234 -storepass totototo -keyalg RSA -keysize 2048 -validity 10000`

## Zipalign

For optimized run, APKs need to be aligned. Zipalign is part of Android SDK tools.

`zipalign -v 4 myapk.apk myaligned.apk`

## DEX hacking

I used my `dexrehash.pl` script, which recomputes a correct size, checksum and SHA1 for the DEX. This script requires Adler32 Perl package.

`dexrehash.pl --input ./classes.dex --fix`

This tool is on **github.com/cryptax/dextools**

## APK Sign

New APK use APK Sign v2 which is not supported by `jarsigner`.
The new way to sign the apk is with `apksigner`, from Android SDK tools.

`/home/axelle/Android/Sdk/build-tools/29.0.3/apksigner sign --ks ph0wn.keystore myapk.apk`


# Encrypted strings

Ph0wn payload strings are encrypted to make it less obvious they are written backwards.
I use the Blowfish algorithm.
To generate encrypted strings, use `java EncryptedStrings`


# Tested

It has been tested on:

- Android emulator 8.0 x86_64
- Samsung Galaxy S7 Edge Android 8.0.0
- Samsung J5 with Lineage


