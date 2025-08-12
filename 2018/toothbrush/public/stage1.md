# Healing the Toothbrush - Stage 1

Category: Android, Reverse
Author: cryptax
Points: 500 - 20 - 100


Ph0wn aliens have abducted my smart toothbrush.

My toothbrush has hidden this horrible episode far in its subconscious mind, but I know encrypted memories of the event are still there... My psychiatrist tells me I need to get my toothbrush talk, that it will help it heal.

To do so, the psychiatrist advises, as a first step, to find the **decryption key** to those events. This key is hidden within the official Android application `Beam_v1.3.3_apkpure.com.apk` (sha256: `df8956a138a05230fb26be27a22dc767775b55b1d2250be25aa899c8bbee53b9`). 

My psychiatrist provides the following information:

- The toothbrush uses **Bluetooth Low Energy** to communicate.
- It is useless to understand the entire application. You should concentrate on what handles toothbrush events. The class for those events is called `BrushEvent`.
- [Is my toothbrush really smart?](https://download.ernw-insight.de/troopers/tr18/slides/TR18_NGI_BR_Is-my-toothbrush-really-smart.pdf)


**Important** :

- In this stage, you **do not need the smart toothbrush**.
- Do not try to install the Android application on your smartphone: it is not malicious but it requires a client login (which you don't have) to operate, so it will be useless to run...
- Please **do not connect to the toothbrush via Bluetooth**, it may cause service disruptions for stage 2. And it won't help for stage 1. Actually, if you don't need Bluetooth on your smartphone or laptop, we recommend you **disable** it.
- For this stage, you need to flag `ph0wn{hexstring of encryption key}`. The encryption key is required to complete stage 2.
