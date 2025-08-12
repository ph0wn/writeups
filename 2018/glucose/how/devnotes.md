# Idea

The idea behind this challenge is to have participants play with MQTT which is used very much for IoT devices.

In MQTT, the philosophy is that we have a server on to which some people may publish messages to given topics, and other people (or the same) may listen (subscribe) to given topics to receive messages posted there.

For this challenge, participants will:

- need to understand what a MQTT topic is
- subscribe to "all" topics using the `#` wildcard
- understand publications and subscribtions are authenticated. They will need to find the admin credentials in the Android application.
- have the idea of trying to lower glucose level readings beyond normal range

# MQTT Server

A few free test brokers exist (such as `test.mosquitto.org`) but everyone can access/publish on there, which isn't very suitable for a CTF.

Instead we will setup our own MQTT server inside a Docker container.
We re-use the official `eclipse-mosquitto` image, in which we add our own scripts.

We setup password authentication on the MQTT server and set password for various accounts: user, admin and superadmin. The password file is created via the command `sudo mosquitto_passwd -c /etc/mosquitto/passwd username`

We setup Mosquitto with ACL. In particular, we don't want standard users to have the ability to post on topics, on listen.

The configuration file for the Mosquitto server is `mosquitto.conf`

# Topics

For this challenge, we have several roles:

- **user**: standard user level. The credentials are given in the public description.
- **admin**: refers to a user with higher priorities. We expect the participant to find the credentials for this account in the Android application.
- **superadmin**: we don't want participants to find the password for this account (would possibly spoil some things). That's the account for us to publish messages we want.

For this challenge, we define several topics:

- **glucose-level**: this topic reports the current glucose level of the patient. Totally fake of course, it's just a random number in an acceptable range ;) This topic can be read by everyone but only written by admin and superadmin.

- **info**: this topic conveys a hint for participants that they should connect as admin. This topic can be read by everyone, but only written by superadmin.

- **alert**: this topics only displays messages in specific situations, for example when the glucose-level is out of range. Only superadmin can write this.

- **teamid**: this is a topic to supply the current team identifier of the team which is admin and retrieving the flag. Only an admin (and superadmin) can read and write here.

- **flag**: very specific topic that shows the encrypted flag. Only superadmin can write this and you'll need to be admin to read.

# Encrypting the flag

The problem with MQTT is that there is no session, so we can't publish messages only to some particular clients. Once a participant has modified the glucose level out of range, we need to send the flag, but we can't send it unencrypted because everybody would be able to read it!

So, we need to encrypt it. But with which key?
We generate (once) **one  key per team and will provide them by other means**.
To get the encrypted flag, the participant needs:

1. To become admin,
2. Set his correct team id
3. Do the correct hack to receive the encrypted flag.
4. The flag is encrypted with the team key.

# Android application

The Android application is a MQTT client app.
It subscribes to:

- ph0wn18/glucose-level and displays the current level (middle zone)
- ph0wn18/alert and displays the health alert (bottom zone)
- ph0wn18/info and displays information (top zone)

Admin credentials are hidden in the application.



