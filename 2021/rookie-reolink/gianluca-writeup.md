# ph0wn 2021 reolink challenge

- Category: Rookie, Forensics
- Author: cryptax
- Write-up author: Gianluca
- Points: 18
- 18 teams solved the challenge

## Description

Uncle Picsou protects his safe with a Ph0wn Reolink Wifi Camera RLC-410W.
Nevertheless, Pico le Croco managed to rob him...
Find the proof in the camera's firmware.

camera-firmware `6eb1d3c367adcffc6f7cf1129cfc01ccaef45511c7aa316b0935c37f302e3800`

## First Step
I used 7zip on `camera-firmware` file, finding `flag.txt.gz` file.

## Second Step
I used gunzip on `flag.txt.gz` finding `flag.txt` file which contained flag

## Solution
Flag: `ph0wn{d4nt_ever_buy_crocod1le_bag_or_sh0es}`
