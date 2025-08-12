# Requirements:
# Android SDK: API level 14, tools, build tools
# unzip
# jarsigner
# smali and baksmali version 2.2b4
# java
# ant

SMALI_DIR=/home/axelle/softs

all: alarm.apk

.PHONY: clean test install uninstall

bin/SettingsActivity-release.apk:
	sed -i 's/DEBUG.=.true/DEBUG = false/g' src/ph0wn/ctf/alarm/SettingsActivity.java
	ant release

bin/SettingsActivity-debug.apk:
	sed -i 's/DEBUG.=.false/DEBUG = true/g' src/ph0wn/ctf/alarm/SettingsActivity.java
	ant debug

hack-smali/ph0wn/ctf/alarm/b.smali: bin/SettingsActivity-release.apk
	@echo "--> Unzipping apk"
	unzip bin/SettingsActivity-release.apk -d unzipped
	java -jar ${SMALI_DIR}/baksmali.jar d -o ./hack-smali ./unzipped/classes.dex

out.dex: BadLoginException.smali.hack hack-smali/ph0wn/ctf/alarm/b.smali
	@echo "--> Hacking smali"
	cp BadLoginException.smali.hack ./hack-smali/ph0wn/ctf/alarm/b.smali
	java -jar ${SMALI_DIR}/smali.jar a ./hack-smali

alarm.apk: out.dex ph0wn.ks
	@echo "--> Rebuilding APK"
	mv out.dex ./unzipped/classes.dex
	rm -rf ./unzipped/META-INF
	cd unzipped; zip --quiet -r ../alarm.apk ./* ; cd ..
	jarsigner -keystore ph0wn.ks -storepass 12345678 -keypass 12345678 $@ ph0wn

test: DecryptSettings.class Mycrypto.class

DecryptSettings.class: DecryptSettings.java
	javac $<

Mycrypto.class: Mycrypto.java
	javac $<

install: 
	adb install alarm.apk

uninstall:
	adb uninstall ph0wn.ctf.alarm

clean:
	ant clean
	rm -rf unzipped hack-smali out.dex alarm.apk *.class
