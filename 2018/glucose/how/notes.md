# References

http://blog.thelifeofkenneth.com/2016/07/driving-leds-on-raspberry-pi-via-mqtt.html
http://www.steves-internet-guide.com/into-mqtt-python-client/
- [SYS topics](https://github.com/mqtt/mqtt.github.io/wiki/SYS-Topics)
- [Mosquitto configuration file](https://mosquitto.org/man/mosquitto-conf-5.html)
- [Python MQTT sources](https://github.com/eclipse/paho.mqtt.python)
- [Eclipse Mosquitto Dockerfile](https://github.com/eclipse/mosquitto/blob/master/docker/1.4.14/Dockerfile)
- [AES encryption/decryption with padding](http://www.codekoala.com/posts/aes-encryption-python-using-pycrypto/)
- [Supervisor configuration](http://supervisord.org/configuration.html)

# Free test brokers

- test.mosquitto.org
- broker.hivemq.com
- iot.eclipse.org

# Test code

Publishing:
```
import paho.mqtt.client as mqtt
broker_address = "test.mosquitto.org"
client = mqtt.Client("Me")
client.connect(broker_address, 1883)
client.publish("xpert2018/demo/chal", "Hello fortinet")
```


To subscribe:

```python
def on_message(client, userdata, message):
    print("message received " ,str(message.payload.decode("utf-8")))
    print("message topic=",message.topic)
    print("message qos=",message.qos)
    print("message retain flag=",message.retain)
client.on_message=on_message
client.loop_start()
client.subscribe('xpert2018/#')
```

NB. You cannot subscribe to **all** messages (it seems)

# MQTT Tools

They are in the package `mosquitto-clients`

Subscribing:

`mosquitto_sub -v -h broker_ip -p 1883 -t '#'`
`mosquitto_sub -v -h 127.0.0.1 -p 1883 -t '#' -u user -P expert`

Publishing:

`mosquitto_pub -h test.mosquitto.org -t 'xpert2018/demo/chal' -m 'hi there'`
`mosquitto_pub -h 127.0.0.1 -t "test" -m "hello world" -u "user" -P "expert"`

[Docker](https://hub.docker.com/_/eclipse-mosquitto/):
- `docker pull eclipse-mosquitto`
- `docker run -it -p 1883:1883 -p 9001:9001 -v /mosquitto/data -v /mosquitto/log eclipse-mosquitto`

To connect to the Docker container: `docker exec -it container /bin/sh`
To retrieve the container's config: `docker cp container:/mosquitto/config/mosquitto.conf .`
To use one's config, password file and ACL, create a Docker image and use it.
`docker run -it -p 1883:1883 --name xperts18-mqtt xperts18/mqtt:latest`

# Wireshark

Connect consists in:
- TCP to test.mosquitto.org (37.187.106.16) port 1883
- MQ Telemetry Transport Protocol
- Connect Command
- Client Id: Me

We have a connect ack, with result 0 for "Connection Accepted"

Publishing a message: we see the message going to mosquitto, and then sent by mosquitto.
It is a "Publish Message" with Topic: "xpert2018/demo/chal" and Message: ...

# MQTT authentication

1. Password file to be written with `sudo mosquitto_passwd -c /etc/mosquitto/passwd username`
2. Configure Mosquitto `/etc/mosquitto/conf.d/default.conf` to set:

```
allow_anonymous false
password_file /etc/mosquitto/passwd
```


http://unitslab.com/fr/node/1

