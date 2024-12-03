#!/bin/bash

# Install WHAD using instructions @ https://whad.io

if test $# -ne 1; then
	echo "Usage: $0 <PCAP file>"
	exit 1
fi

pcap=$1

echo "[i] Extracting the key..."
key=$(wplay --flush $pcap 2> /dev/null | wanalyze key_cracking.key 2> /dev/null)
echo "    -> found key: $key"

echo "[i] Extracting audio stream..."
out="/tmp/stream.wav"
wplay --flush $pcap -d -k $key 2> /dev/null | wanalyze --raw audio.raw_audio 2> /dev/null > $out
echo "    -> saved as: $out"

echo "[i] Playing audio stream."
aplay $out

echo "[i] Done !"
