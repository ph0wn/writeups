#/bin/bash

flag="ph0wn{cavi@r_secUreD}"
# we have 44 data bits - that's a little more than 5 characters

for ((i=0; i<${#flag}; i+=5)); do
    chunk="${flag:i:5}"
    out="b$((i/5+1)).wav"
    echo "----- Calling distress with payload=$chunk and out=$out"
    go run ./mhv/distress.go --outwav "$out" --payload "$chunk"
done

# sudo apt install sox
sox b*.wav ph0wn-beacon.wav


