# Requirements:
# Android SDK: API level 14, tools, build tools
# unzip
# jarsigner
# smali and baksmali version 2.2b4
# java
# ant

SMALI_DIR=/home/axelle/softs

all: ph0wn.apk

.PHONY: clean install uninstall

bin/MainActivity-release-unsigned.apk:
	ant release

bin/MainActivity-debug.apk:
	ant debug

ph0wn.apk: bin/MainActivity-debug.apk ph0wn.ks
	cp $< ph0wn.apk
#	jarsigner -keystore ph0wn.ks -storepass 12345678 -keypass 12345678 $@ ph0wn

ph0wn.ks:
	keytool -genkey -alias ph0wn -v -keystore ph0wn.ks

install: ph0wn.apk
	adb install $<

uninstall:
	adb uninstall ph0wn.reconjet

clean:
	ant clean
	rm -f ph0wn.apk

