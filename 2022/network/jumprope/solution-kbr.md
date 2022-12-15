# ph0wn 2022 Jump Rope challenge
* Category: Network
* Author: cryptax
* Author of the write-up: kbr (Re1n3r!)
* Points: 500
* Solves: 2

# Description
People say crocodiles are lazy. This doesn't please Pico le Croco. He
challenges you with a connected jump rope. Are you fit? It's time to prove
it!

The jump rope, manufactured by Renpho, is connected by Bluetooth to a
[smartphone app](https://apkpure.com/renpho-fit/com.renpho.fit)
(com.renpho.fit), and features 3 different modes:

* Jump for a target amount of time
* Jump for a target amount of jumps
* Free mode, where you do want you want

At ph0wn, we want geeks, right? So, Pico would like you to initiate a jump
workout session for 1337 target jumps without the smartphone app (i.e you
must understand how to do it on your own).

Go on! Jump!

## Device info

* Come and borrow one of the 2 jump ropes at the organizer's desk. Book a
  slot.
* Safety remark: when you jump, we kindly ask you to ensure you won't break
  anything around you, or hurt anyone (or yourself). You can go outside to
  test it. If you are disabled, you can still complete the challenge :)
* Do not modify the length of the rope, and please handle it with care.
* Do not attempt to flash a new firmware on the jump rope.
* Do not open, tear down the jump rope! This is not a hardware challenge.
* Do not fuzz the jump rope: the security of this smart object is uncertain
  (lol), and you're likely to brick it. Do not try anything that might
  brick the jump rope. You should only do things you understand...
* It's not your time slot? Please disconnect and do not send BLE packets
  to the jump rope. Be fair.
* If you absolutely need to reset the jump rope, remove the batteries and
  replace them to reset the device.

## Flag! I want the flag!

To validate your answer - and prove you are not cheating - please send the
adequate Bluetooth commands on one of our BLE PH0WN-VALIDATION-ROPE
devices. There should be 3 (#1, or #2, or #3). Those Ph0wn Validation
Ropes replicate the connected jump rope, except there is no rope, but a
flag ;P.

The Ph0wn Jump Rope won't help you solve this challenge. Don't try to hack
it, you are losing your time. If you are unable to connect to any of the
validation ropes, come to the organizers desk and ask us to reboot one.
Once you have your flag, please disconnect from the device.

# Reversing
We want to see how the app talks to the rope, so we can start by reversing
the app without booking a time slot to get access to the device.

Loading the APK into JADX (`jadx -d disasm/ Renpho\ Fit_1.9.14_Apkpure.apk`),
we find many activities and services in `resources/AndroidManifest.xml`.
Most of them are below the `com.renpho.fit` package, so that seems like a
good place to start. In there, there are about 750 classes, so let's see
if we can find more interesting sub-packages:

```
$ cd sources/com/renpho/fit
$ find -type d
.
./common
./common/loadmore
./common/receiver
./common/receiver/event
./common/service
./generated
./generated/callback
./ble
./ui
./ui/setting
./ui/setting/sub
./ui/personal
./ui/personal/sub
./ui/personal/sub/fragment
./ui/viewmodel
./ui/article
./ui/home
./ui/home/mode
./ui/home/weekstatistics
./ui/home/goal
./ui/home/timecd
./ui/home/numbercd
./ui/home/freeskip
./ui/home/challenge
./ui/home/ranklist
./ui/login
./views
./views/seekbar
./views/scaleruler
./databinding
./bean
./network
./utils
./photo
./dao
./dao/daoEntity
./base
```

`com.renpho.fit.ble` sounds like a good place to start:

```
$ tree ble/
ble
├── BleDataHandle.java
├── BleLiveData.java
├── BleOffLineDataHandler.java
├── Command.java
└── RScan.java

0 directories, 5 files
```

In `Command.java`, we find a few public functions that return byte arrays
and are called `timeCommand()`, `test()`, `otaMode()`, `countDownNumTargetValue(int)`,
`stopSport(int)`, `freeJumpMode()`, etc. All of these build a byte array
by passing (mostly integer literals) to functions such as
`DeviceUtil.intToByte` or `DeviceUtil.inToByte{Four,Three,Two,One}`:

```java
public static byte[] countDownNumTargetValue(int i) {
    byte intToByte = DeviceUtil.intToByte(2);
    byte inToByteTwo = DeviceUtil.inToByteTwo(5);
    byte inToByteOne = DeviceUtil.inToByteOne(5);
    byte intToByte2 = DeviceUtil.intToByte(129);
    byte inToByteFour = DeviceUtil.inToByteFour(i);
    byte inToByteThree = DeviceUtil.inToByteThree(i);
    byte inToByteTwo2 = DeviceUtil.inToByteTwo(i);
    byte inToByteOne2 = DeviceUtil.inToByteOne(i);
    byte[] bArr = {intToByte, inToByteTwo, inToByteOne, intToByte2, inToByteFour, inToByteThree, inToByteTwo2, inToByteOne2};
    byte[] hexStringInputToBytes = CRCCheckUtil.hexStringInputToBytes(DeviceUtil.Bytes2HexString(bArr));
    byte[] bArr2 = {intToByte, inToByteTwo, inToByteOne, intToByte2, inToByteFour, inToByteThree, inToByteTwo2, inToByteOne2, hexStringInputToBytes[0], hexStringInputToBytes[1]};
    Log.e("TAG", "HEX=" + DeviceUtil.Bytes2HexString(bArr2) + ">>>>>>>>>>" + CRCCheckUtil.getOuputHex(DeviceUtil.Bytes2HexString(bArr)));
    return bArr2;
}
```

A quick check of these `DeviceUtil` functions confirms our hunch: they are
just used to encode big-endian 16-bit and 32-bit numbers. To round it off,
for whatever reason, there is also a 16-bit CRC appended to the command
sequence.

Now we could try building a byte array by hand that follows this logic, or
we could use some hooking with frida to get the result. Static analysis
seems too tedious, because we'd have to reverse the used CRC (which really
is not that complicated, we just didn't want to think too after 6 hours of
CTF).

Luckily, there is a call to `android.util.Log.e()` which will print the
resulting command sequence including the CRC (and the CRC again, for good
measure). As this call is not guarded by a condition, we can be sure to
see the output on `adb logcat`. It's time to install the app and book the
rope. (At this point, it would have been a good idea to check if this
function is actually called, but we felt confident that we were on the
right track.)

# Dynamic Analysis
We had a rooted Android phone with us, which we don't care too much about,
so we could just install the Renpho app there. (We also somehow assumed
that the ph0wn organizers are not trying to pwn us. Using an emulator
would've been prudent, but who has the time to wait for that download to
finish...)

The app asks you to create an account, but all that can be skipped. After
pairing to the jump rope, we can choose the mode *number of jumps* and
luckily, we can already input the target value of *1337* there. If the UI
had blocked this, we could have used frida to hook the call to
`countDownNumTargetValue` and replace the argument, or we could have logged
the CRC values and reversed it with something like [crcbeagle](https://github.com/colinoflynn/crcbeagle).
Starting our session while attached to logcat, we see, amongst other things:

```
$ adb logcat -s TAG:V
[...]
[...] E TAG: HEX=0100063012000A0C165F78>>>>>>>>>>5F78
[...]
[...] E TAG: HEX=0200058100000539DB3E>>>>>>>>>>DB3E
[...]
```

A brief survey of the other command functions, we note that `010006...` is
the result of `timeCommand()`. 0x0539 == 1337, so that looks as expected
too.

Now we can start implementing a small python script using [bleak](https://github.com/hbldh/bleak)
to interact with the rope:

```python
import asyncio
import sys
from bleak import BleakClient


COMMAND_CHAR_UUID = "<placeholder>"


async def cmd(client, cmd):
    print(f"writing {cmd.hex()}")
    await client.write_gatt_char(COMMAND_CHAR_UUID, cmd)
    print("written ok")


async def main(address):
    print(f"connecting to {address=}")
    async with BleakClient(address) as client:
        print("connected")
	# timeCommand, no idea if this is required but it can't hurt
        await cmd(client, bytes.fromhex("0100063012000A0C165F78"))
	# countDownNumTargetValue(1337)
        await cmd(client, bytes.fromhex("0200058100000539DB3E"))


asyncio.run(main(sys.argv[1]))
```

We only need two missing pieces: the address and the UUID of the command
GATT characteristic. The address is shown in the app connection screen,
so that is easy. There are probably hundreds of ways to find the GATT
characteristic - we could for example sift through the
[BTsnoop log](`https://source.android.com/docs/core/connect/bluetooth/verifying_debugging#debugging-with-logs`).
But it is probably hardcoded somewhere in the app, so let's see if we find
it quickly:

```
$ # (still in sources/com/renpho/fit)
$ rg -i character
[about 20 results, but no hardcoded UUID here]
$ rg -i uuid
App.java
10:import android.os.ParcelUuid;
18:import cn.com.heaton.blelibrary.ble.utils.UuidUtils;
40:import java.util.UUID;
120:        ScanFilter build = new ScanFilter.Builder().setServiceUuid(ParcelUuid.fromString(RENPHOFIT_SERVICE)).build();
121:        ScanFilter build2 = new ScanFilter.Builder().setServiceUuid(ParcelUuid.fromString(UuidUtils.uuid16To128(OTA_SERVICE))).build();
125:        Ble.options().setLogBleEnable(true).setThrowBleException(true).setLogTAG("AndroidBLE").setAutoConnect(false).setIgnoreRepeat(false).setConnectFailedRetryCount(3).setConnectTimeout(WorkRequest.MIN_BACKOFF_MILLIS).setScanPeriod(3600000L).setMaxConnectNum(7).setScanFilter(arrayList).setUuidService(UUID.fromString(RENPHOFIT_SERVICE)).setUuidServicesExtra(new UUID[]{UUID.fromString(UuidUtils.uuid16To128(OTA_SERVICE))}).setUuidWriteCha(UUID.fromString(RENPHOFIT_PROPERTIES_WRITE)).setUuidNotifyCha(UUID.fromString("00005303-0000-0041-4c50-574953450000")).create(this, new Ble.InitCallback() { // from class: com.renpho.fit.App$initBle$1

ble/RScan.java
[about 30 boring results]
```

App.java:125 looks very interesting. `setUuidWriteCha()` explains why we
didn't find anything with our first search. `RENPHOFIT_PROPERTIES_WRITE`
is `"00005302-0000-0041-4c50-574953450000"`, so that looks promising.


Now we can run the script and the rope makes loud beeping noises and shows
1337 on the screen! (That is, if we don't forget to disconnect our phone
first...)

# Getting the flag
Finally, it is time to run this against the validation devices, as these
are supposed to print out the flag after successful attack. Trying to run
the script with the address of any of the validation devices, we get this
error:

```
bleak.exc.BleakDBusError: [org.bluez.Error.Failed] le-connection-abort-by-local
```

I guess that is Bluetooth for you. After a couple of restarts of the
validation devices by the CTF organizers, and a few more tries, we manage
to connect to one of them. Now where do we get the flag from? Assuming it
should be readable over Bluetooth as well, so we extend our script to read
the `RENPHOFIT_PROPERTIES_READ` characteristic before and after the command:

```python
import asyncio
import sys
from bleak import BleakClient


COMMAND_CHAR_UUID = "00005302-0000-0041-4c50-574953450000"
READ_CHAR_UUID = "00005303-0000-0041-4c50-574953450000"


async def cmd(client, cmd):
    print(f"writing {cmd.hex()}")
    await client.write_gatt_char(COMMAND_CHAR_UUID, cmd)
    print("written ok")


async def main(address):
    print(f"connecting to {address=}")
    async with BleakClient(address) as client:
        print("connected")

        resp = await client.read_gatt_char(READ_UUID)
        print(f"before {resp=}")

	# timeCommand, no idea if this is required but it can't hurt
        await cmd(client, bytes.fromhex("0100063012000A0C165F78"))
	# countDownNumTargetValue(1337)
        await cmd(client, bytes.fromhex("0200058100000539DB3E"))

        resp = await client.read_gatt_char(READ_UUID)
        print(f"after {resp=}")


asyncio.run(main(sys.argv[1]))
```

No luck! Let's see if the validation device implements something else:

```
$ bluetoothctl
[bluetooth]# connect <address>
[CHG] Device <address> Connected: yes
[NEW] Primary Service (Handle 0xnnnn)
[...]
[NEW] Characteristic (Handle 0xnnnn)
[...]
[NEW] Descriptor (Handle 0x0000)
[...]
[<address>]# 
```

A quick check of that output shows a suspicious characteristic which we
haven't seen before: `deadbeef-ff11-aadd-0000-000100000002`. Let's try with that!

```
$ ./venv/bin/python jump.py <address>
connecting to address=<address>
connected
resp=b"you have to jump"
writing 0100063012000A0C165F78
written ok
writing 0200058100000539DB3E
written ok
resp=b"ph0wn{weSeeYouRF1t_GooD}"
```
