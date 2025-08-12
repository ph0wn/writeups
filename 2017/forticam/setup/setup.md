# Setup for FortiCam challenge

The **FortiCam MB40** needs to be protected because its Web UI is :(

In front of the FortiCam, we have:

- a **FortiSwitch PoE**: to get power. 
- a **FortiGate**: to protect the camera

## FortiSwitch

Current setup:

- FortiSwitch 448D-POE,  [[http://10.210.0.74|web interface]] - the equipment is shared and cannot be locked in lab setup.

The switch is connected to:

1. The camera (e.g switch port 25)
2. The FortiGate (e.g switch port 26)

We put both ports in the same native VLAN, e.g 2041. No other option enabled, except IGMP snooping by default.


## FortiGate

Current setup:

- FortiGate 900D, [[https://192.168.195.32|web interface]], locked on Lab Setup - to release ASAP

FG900D-TIGER-195-32

### Make management interface usable

Network > Interfaces > mgmt1 : ?

### Virtual IP

Create a **Virtual IP**: from the fortigate, port 8080, to the camera's IP address port 80. The camera's default IP address is 192.168.1.245. So:

- Interface: mgmt1
- Type: static nat
- External IP address: 192.168.195.32
- Mapped IP address: 192.168.1.245
- Port forwarding: TCP external 8080 to 80


### Policy

We need to accept all flow from the fortigate port 8080 going to our VIP:

- Incoming: mgmt1
- Outgoing interface: the port which is connected to the fortiswtich (e.g 9)
- Source: all
- Destination: our VIP
- Service: all
- Accept
- Web filter: use our forticam web filter
- Log allowed traffic: all sessions

### Web filter profile

We create a specific profile for the FortiCam.

- Disable fortiguard category based filter
- Static URL filter: use URL filter

URL: external IP:8080/cgi-bin/date.cgi*
Type: Wildcard
Action: Block
Status: Enable

What we need to block:

| End of URL                             | Reason |
| --------------------------------------- | ---------- |
| /cgi-bin/date.cgi*		   | System - Forbid changing camera name & exploit... |
| /cgi-bin/security.cgi*          | Security - User: Forbid to change user credentials |
| /cgi-bin/adduser.cgi*         | Security - User: Forbid creation of new accounts |
| /cgi-bin/deleteaccount.cgi* | Security - User: forbid deletion of account |
| /cgi-bin/editaccount.cgi* | Security - User: forbid editing accounts |
| /cgi-bin/httpauthenticate.cgi* | Security - User: Forbid modif on HTTP authentication method |
| /cgi-bin/streamauthenticate.cgi* | Security - User: Forbid modif on streaming |
| /cgi-bin/addipfilter.cgi* | Security - IP filter: forbid creation of IP filters |
| /cgi-bin/ipctrl.cgi* | Network - Basic: forbid change of IP address (DHCP) |
| /cgi-bin/portctrl.cgi* | Network Basic: forbid change of ports |
| /cgi-bin/sdwork.cgi*           | Storage Management - SD card: forbid removal of videos |
| /cgi-bin/sdformat.cgi* | Storage Management - SD card: forbid sd card formatting |
| /cgi-bin/sd_rec.cgi* | Storage Management - SD card: forbid cleanup |
| /cgi-bin/nasformat.cgi* | Storage management - network share: forbid NAS formatting |
| /cgi-bin/admin/hardfactorydefault.cgi* | Factory default: Forbid full and partial restore |
| /cgi-bin/admin/factorydefault.cgi* | Factory default: Forbid full and partial restore |
| /cgi-bin/admin/reset.cgi* | Factory default: Forbid reset |
| /cgi-bin/reset.cgi* | Software upgrade |
| /cgi-bin/checkmainstatus.cgi*  | Software upgrade |
| /cgi-bin/begintoupgrade.cgi*  | Software upgrade |
| /cgi-bin/upload.cgi*  | Software upgrade |
| /cgi-bin/doupgrade.cgi*  | Software upgrade |
| /cgi-bin/upgradeconfig.cgi* | Maintenance |


Do not forget the **star** at the end of wildcards.

Testing:

```bash
$ curl -H "Authorization: Basic YWRtaW46YWRtaW4=" http://192.168.195.32:8080/cgi-bin/date.cgi?system_hostname=taratatata
$ curl -H "Authorization: Basic YWRtaW46YWRtaW4=" http://192.168.195.32:8080/cgi-bin/sdwork.cgi?removefilename=R_20171017_123712.avi%2C&action=delete
```

You get as response a blocked page:

```html
...
<h3>Web Page Blocked!</h3>
<div class="notice">
    <p>The page you have requested has been blocked, because the URL is
banned.</p>
```

This replacement page fortunately does not show and disrupt the Web UI.
In several cases, it looks like the action is working - from the end user's view, for instance, an upgrade will look like it is working, but it is doing nothing except - depending the command - restarting the web server.

In the case of recordings, it looks like videos have been deleted. But if you logout and login, you will see the videos back again.

Example:

1. I remove R-20171020-114500.avi
2. The video is indeed apparently removed from the list.
3. However, on the FortiGate, the command is blocked
4. If we re-do a search in the recording list, the video shows again. Or if you navigate to another tab, and come back, it shows again etc.

```
config webfilter urlfilter
    edit 1
        set name "forticamprotect"
        config entries
            edit 1
                set url "192.168.195.32:8080/cgi-bin/date.cgi*"
                set type wildcard
                set action block
            next
            edit 2
                set url "192.168.195.32:8080/cgi-bin/sdwork.cgi*"
                set type wildcard
                set action block
            next
            edit 3
                set url "192.168.195.32:8080/cgi-bin/security.cgi*"
                set type wildcard
                set action block
            next
            edit 4
                set url "192.168.195.32:8080/cgi-bin/adduser.cgi*"
                set type wildcard
                set url "192.168.195.32:8080/cgi-bin/admin/hardfactorydefault.cgi*"
                set type wildcard
                set action block
            next
            edit 6
                set url "192.168.195.32:8080/cgi-bin/admin/reset.cgi*"
                set type wildcard
                set action block
            next
            edit 7
                set url "192.168.195.32:8080/cgi-bin/deleteaccount.cgi*"
                set type wildcard
                set action block
            next
            edit 8
                set url "192.168.195.32:8080/cgi-bin/editaccount.cgi* "
                set type wildcard
                set action block
            next
            edit 9
                set url "192.168.195.32:8080/cgi-bin/httpauthenticate.cgi*"
                set type wildcard
            next
            edit 10
                set url "192.168.195.32:8080/cgi-bin/streamauthenticate.cgi*"
                set type wildcard
                set action block
            next
            edit 11
                set url "192.168.195.32:8080/cgi-bin/addipfilter.cgi*"
                set type wildcard
                set action block
            next
            edit 12
                set url "192.168.195.32:8080/cgi-bin/ipctrl.cgi*"
                set type wildcard
                set action block
            next
            edit 13
                set url "192.168.195.32:8080/cgi-bin/portctrl.cgi*"
                set type wildcard
                set action block
            next
            edit 14
                set url "192.168.195.32:8080/cgi-bin/sdformat.cgi*"
               set type wildcard
                set action block
            next
            edit 15
                set url "192.168.195.32:8080/cgi-bin/sd_rec.cgi*"
                set type wildcard
                set action block
            next
            edit 16
                set url "192.168.195.32:8080/cgi-bin/nasformat.cgi*"
                set type wildcard
                set action block
            next
            edit 17
                set url "192.168.195.32:8080/cgi-bin/admin/hardfactorydefault.cgi*"
                set type wildcard
                set action block
            next
            edit 18
                set url "192.168.195.32:8080/cgi-bin/admin/factorydefault.cgi*"
                set type wildcard
                set action block
           edit 19
                set url "192.168.195.32:8080/cgi-bin/admin/reset.cgi*"
                set type wildcard
                set action block
            next
            edit 20
                set url "192.168.195.32:8080/cgi-bin/reset.cgi*"
                set type wildcard
                set action block
            next
            edit 21
                set url "192.168.195.32:8080/cgi-bin/checkmainstatus.cgi*"
                set type wildcard
                set action block
            next
            edit 22
                set url "192.168.195.32:8080/cgi-bin/upload.cgi*"
                set type wildcard
                set action block
            next
            edit 23
                set url "192.168.195.32:8080/cgi-bin/doupgrade.cgi*"
                set type wildcard
               set action block
            next
            edit 24
                set url "192.168.195.32:8080/cgi-bin/upgradeconfig.cgi*"
                set type wildcard
                set action block
            next
        end
    next
end
```

[Security Profile Documentation](http://docs.fortinet.com/uploaded/files/3648/fortigate-security-profiles-56.pdf)

### Monitor

See traffic on Log & Report > Web Filter, or Log & Report > Forward Traffic.

## FortiCam

The camera is set with:

- Hostname: ph0wnCTF (no space)
- Timezone: France
- NTP: ntp.ubuntu.com
- Network: get IP address automatically (192.168.1.245 by default)

## How the challenge was done

I set up Recording, scheduled at a given exact date and time, for a few seconds. The recording goes on the SD card I have inserted.

## Documentation

[FortiSwitch 108D PoE](http://docs.fortinet.com/uploaded/files/2096/FortiSwitch-108D-POE-QuickStart.pdf).

## Troubleshooting

The default IP address of the webcam is `192.168.1.245`.
If you need to search for it: `nmap -sP 192.168.1.*`

### Stream

On port 8008
- [See the stream of images](http://192.168.194.122:8008/)
- [Webcam GUI](http://192.168.194.122)


### Resetting the Web UI

Log on the web ui, maintenance, and upload `config_file.bin`.

Make sure the videos are still presend on the SD card.

### Restoring the videos

Copy the record directory as such on the SD card.
The SD card is to be inserted face down when seeing the front of the camera.
To remove the SD card, might need to open it...

```
sudo mount /dev/sde1 /mnt/usbkey
cd keep-secret/SDcard-content
sudo cp -R . /mnt/usbkey/
sudo umount /mnt/usbkey
```

Then check:

```bash
$ tree /mnt/usbkey/
/mnt/usbkey/
 Record
    20170613
         14
	 15
	     R...
	     R...
    20171013
          11
	     R...
	     R_20171013_111835.avi
...

10 directories, 9 files
```



### Resetting the camera

If the webcam is really in bad state, you'll have to perform a hardware reset: that's the small button on the side, next to the SD card. Press it for 20 seconds.

Then, connect to the webcam (245), upload the `config_file.bin` or ensure to change the IP address.
If possible don't check through the web interface that something's on the SD card, because everything goes in the logs and that'll give people hints...
