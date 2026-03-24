# Flash and Debug the board

You will need `LinkServer.zip` to connect with GDB. Download it from [http://chal.ph0wn.org:9000/](http://chal.ph0wn.org:9000/).

There is one version per supported OS and architecture (Windows, Mac, Linux for x64 and aarch64).

LinkServer has been tested successfully on Ubuntu 22, Ubuntu 24 and Windows.

## Easy setup in Linux

### For Ubuntu users

If you are using a recent Ubuntu distribution, everything should just work after running:

```bash
sudo apt-get update && sudo apt-get install -y \
    libusb-1.0-0-dev dfu-util whiptail usbutils unzip udev
unzip -o LinkServer.zip
chmod u+x LinkServer/LinkServer_25.12.83.linux.x86_64.deb.bin
sudo sh LinkServer/LinkServer_25.12.83.linux.x86_64.deb.bin -- acceptLicense
rm -rf LinkServer
```

If everything went well, you should be able to find `LinkServer` at `/usr/local/LinkServer_25.12.83/LinkServer`.

### For other distributions

This is the simplest method to make LinkServer "just work".
Depending on your exact setup, you could have different issues (like permission problems with udev).
In that case, you will need to adapt the script to your environment.

`LinkServer.zip` needs to be in the same directory as `linkserver.sh`.

Install distrobox (to adapt depending on your OS):
```bash
sudo apt update && sudo apt install distrobox
```

Then run `linkserver.sh`. It will create a working setup for LinkServer on the first execution, and can be used to run LinkServer transparently:
```bash
./linkserver.sh
```

You should see the command help if the installation worked correctly.

**From this point onwards, `linkserver.sh` should be used directly as a replacement of `LinkServer` in the rest of this documentation if you went with this method.**

## Check that LinkServer works

Make sure the udev rules work correctly.
The exact procedure will depend on your distribution.

The following command should work:
```
lsusb -d 1fc9:0143 -v
```

Then run `LinkServer`:
```
LinkServer probes
```

If it works correctly, you should see something like:

| # | Description | Serial | Device | Board | Capabilities |
|:--|:---|:---|:---|:---|:---|
| 1 | MCU-LINK FRDM-MCXN947 (r0E7) CMSIS-DAP V3.128 | HFWCJIYVIOI4I | MCXN947 | FRDM-MCXN947 | DEBUG, VCOM, SIO |

## Flash

The board comes already flashed correctly, but in case:

```
LinkServer flash MCXN947 load picobank.bin:0
```

## Debug with GDB

Start the GDB server on port 3333:

```
LinkServer gdbserver MCXN947
```

In another terminal, connect to the instance:

```
gdb
> target remote :3333
```

# Picocom

- If you use `picocom`, we recommend using the `-c` flag to get back the echo.
- Ctrl-J sends an `\n` on the serial port with the default configuration.

