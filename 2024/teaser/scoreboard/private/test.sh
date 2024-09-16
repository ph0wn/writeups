#!/bin/bash

SCOREBOARD_URL=teaser.ph0wn.org


wrong=$(curl -k -s -X POST -d 'nickname=cryptax&flag=aaaaa' https://${SCOREBOARD_URL}/hurrayifoundtheflag/submit)
if echo "$wrong" | grep -qi "incorrect flag"; then
    echo "[+] Wrong flag test success"
else
    echo "[-] WRONG FLAG TEST FAILED"
fi

echo "Testing correct stage 1 after sleep"
sleep 60

curl -k -s -X POST -d 'nickname=cryptax' --data-urlencode 'flag=ph0wn{t0_the_skY_&_beyonD}' https://${SCOREBOARD_URL}/hurrayifoundtheflag/submit

echo "Testing correct stage 2 after sleep"
sleep 60

curl -k -s -X POST -d 'nickname=cryptax&flag=ph0wn{theSt4rsSh1neInTh3SkY}' https://${SCOREBOARD_URL}/hurrayifoundtheflag/submit

sleep 60

curl -k -s -X POST -d 'nickname=picolecroco&flag=ph0wn{theSt4rsSh1neInTh3SkY}' https://${SCOREBOARD_URL}/hurrayifoundtheflag/submit

sleep 60

curl -k -s -X POST -d 'nickname=penguin&flag=ph0wn{theSt4rsSh1neInTh3SkY}' https://${SCOREBOARD_URL}/hurrayifoundtheflag/submit

echo "Stage 3..."
sleep 60

curl -k -s -X POST -d 'nickname=cryptax&flag=ph0wn{you_beAt_the_aliens_congratZ!!!!!}' https://${SCOREBOARD_URL}/hurrayifoundtheflag/submit



