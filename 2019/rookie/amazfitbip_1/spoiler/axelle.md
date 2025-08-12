To install a watch face, we can use a trick mentioned [here](https://sociofab.com/how-to-install-new-watch-faces-on-amazfit-bip-and-pace-watches/).

For that, you need:

1. MiFit app.
2. Add the device to the MiFit
3. Download a watchface (which ever one) and install it to the smartwatch

Then, use the trick to overwrite the downloaded watch face with the one of the challenge. The directory to write to is `/storage/emulated/0/Android/data/com.xiaomi.hm.health/files/watch_skin_local/xxxx`.

```
root@surnia:/storage/emulated/0/Android/data/com.xiaomi.hm.health/files/watch_skin_local/39e3c0a9f99eecdf4872ac53f359093d # ls -al
total 144
drwxrwx--x 2 u0_a135 sdcard_rw  4096 2019-09-26 14:39 .
drwxrwx--x 4 u0_a135 sdcard_rw  4096 2019-09-26 14:38 ..
-rw-rw---- 1 u0_a135 sdcard_rw 22941 2019-09-26 14:39 39e3c0a9f99eecdf4872ac53f359093d.bin
-rw-rw---- 1 u0_a135 sdcard_rw  2631 2019-09-26 14:38 e268a19dba1f220dc238c96fe70e27ba.png
-rw-rw---- 1 u0_a135 sdcard_rw   147 2019-09-26 14:39 infos.xml
```

Then, overwrite:

```
$ adb push amafitbip1.bin /storage/emulated/0/Android/data/com.xiaomi.hm.health/files/watch_skin_local/39e3c0a9f99eecdf4872ac53f359093d/39e3c0a9f99eecdf4872ac53f359093d.bin
amafitbip1.bin: 1 file pushed. 0.8 MB/s (167529 bytes in 0.208s)
```

And re-sync.
The flag is on the top right corner.


