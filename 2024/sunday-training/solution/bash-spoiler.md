```
$ grep -o '<gpxtpx:hr>[0-9]\+</gpxtpx:hr>' walk.gpx | sed 's/<[^>]*>//g' | while read -r ascii; do printf "\\$(printf '%03o' "$ascii")"; done; echo
ph0wn{thereIsNoB3tt3rWalk4roundAntibes}ph0wn{thereIsNoB3tt3rWalk4roundAntibes}....
```
