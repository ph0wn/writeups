#!/bin/bash

FRONT_DIR=./Ph0wnFrontend
PAYLOAD_DIR=./Ph0wnPayload
DEXREHASH=/home/axelle/git/dextools/dexrehash/dexrehash.pl
BUILD_TOOLS=/home/axelle/Android/Sdk/build-tools/29.0.3/

rm -f signed.apk

echo "Appending a backward payload..."
unzip ${PAYLOAD_DIR}/app/build/outputs/apk/release/app-release-unsigned.apk -d ./unzipped-payload
unzip ${FRONT_DIR}/app/build/outputs/apk/release/app-release-unsigned.apk -d ./unzipped-front
< ./unzipped-payload/classes.dex xxd -p -c1 | tac | xxd -p -r > ./unzipped-front/tac-payload.dex
cd ./unzipped-front
cat classes.dex tac-payload.dex > tmp.dex
mv tmp.dex classes.dex

echo "Patching the DEX..."
${DEXREHASH} --input ./classes.dex --fix

echo "Preparing the APK..."
mv classes.dex.patched classes.dex
rm -f tmp.dex tac-payload.dex
zip -r ../unsigned.apk .

echo "Aligning and signing the APK..."
cd ../
${BUILD_TOOLS}/zipalign -v 4 unsigned.apk aligned.apk
${BUILD_TOOLS}/apksigner sign --ks ./ph0wn.keystore aligned.apk
mv aligned.apk signed.apk

echo "Cleaning up..."
rm -f unsigned.apk
rm -r ./unzipped-payload ./unzipped-front
