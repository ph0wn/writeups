# Setup procedure 

The challenge requires a MQTT server to run.
This is implemented in a Docker container and **requires open port 1883**.

We create a virtual IP address for the MQTT server: participants see it as `10.210.17.67`. In reality, this address is mapped to the challenge server `10.210.17.66` on port 1883.

FortiGate configuration:

- Create a VIP: 10.210.17.67:1883 mapping to 10.210.17.66:1883
- Create a firewall rule accepting all traffic from ph0wn to 10.210.17.67:1883

Reason:

- If participants do a port scan on `10.210.17.67` they will only see port 1883. If they had scanned ports on `10.210.17.66`, they would have seen open ports for all challenges.

```
sudo nmap -Pn -p 0-2000 -sT 10.210.17.67

Starting Nmap 7.01 ( https://nmap.org ) at 2018-11-22 08:42 CET
Nmap scan report for 10.210.17.67
Host is up (0.00026s latency).
Not shown: 1998 filtered ports
PORT     STATE  SERVICE
113/tcp  closed ident
1883/tcp open   unknown
2000/tcp open   cisco-sccp

Nmap done: 1 IP address (1 host up) scanned in 8.46 seconds
```

## Starting the MQTT server

0. Login on the server and go to the glucose challenge directory. 
1. Build the Docker image: `make build`
2. Run a Docker container: `make run`

```
$ docker stop ph0wn18-mqtt
ph0wn18-mqtt
docker rm ph0wn18-mqtt
ph0wn18-mqtt
docker run -d -p 1883:1883 --name ph0wn18-mqtt ph0wn18/mqtt:latest
abf1900d0e7dfa479e1e7e8a95f0b940dbe80281257ac517e56227c5cbbf84fc
```

## Verification

On a different host, launch:
`mosquitto_sub -v -h 10.210.17.67 -p 1883 -t 'ph0wn18/#' -u user -P expert`
Check you receive glucose-level every 20 seconds.

On a different host, launch the solution: `python mqtt-solve.py` and check it finds the flag.
Then **stop and restart the MQTT server**.

## Passwords (Keep Secret from Teams)

| Username | Password | password file entry | 
| ------------- | ------------- | -------------------------- |
| user         | expert      | `user:$6$9vN5zCsnZVhlEXeq$Py+NyLqmpO6WcXgW2yuWo8oP9PhnLiVij+yzvskfcPGy0ZVwVXMymJAz/lNH66rhlFwFewzZ8wD3Ol4WwoLLLQ==` |
| admin      | Sugar1sBaaaaaaad | `$6$IlRzSw5E2xCV8/Hn$I/K7qvkYIomjmisNZS12lhCCGDu+PhdWgtkNe4y8wZJxYIzp2/5YSCQAqY0zP5PTT3HQkM1tiKVCAw7iDjPIvA==` |
| superadmin | Th1sOneMustBeVerySecuRe@ | `superadmin:$6$hO0CKUUsmbBghSsq$qqQVUdeWWnUADGZmtKAVBvM1GesRix2QkFY2wBM4zlz3coAOyQxDDmxav2DUnUsxuwO2pjMqP3zCqo+YkqKy4Q==` |

## Troubleshooting

### Docker

#### make run does not work

If the `docker stop` and `docker rm` commands do not work, simply run `docker run -d -p 1883:1883 --name ph0wn18-mqtt ph0wn18/mqtt:latest`

#### Docker container

Check that Docker container is up and running: `docker ps`

```
abf1900d0e7d        ph0wn18/mqtt:latest   "/docker-entrypoint."   29 seconds ago      Up 28 seconds       0.0.0.0:1883->1883/tcp                     ph0wn18-mqtt
```

Check that port **1883** is listed.

#### Connect to container

`docker exec -it ph0wn18-mqtt  /bin/sh`

#### Logs

```
$ docker logs ph0wn18-mqtt
2018-05-17 09:05:34,041 CRIT Supervisor running as root (no user in config file)
2018-05-17 09:05:34,043 INFO supervisord started with pid 1
2018-05-17 09:05:35,046 INFO spawned: 'mosquitto' with pid 7
2018-05-17 09:05:35,047 INFO spawned: 'admin' with pid 8
2018-05-17 09:05:35,049 INFO spawned: 'superadmin' with pid 9
2018-05-17 09:05:36,139 INFO success: mosquitto entered RUNNING state, process has stayed up for > than 1 seconds (startsecs)
2018-05-17 09:05:36,139 INFO success: admin entered RUNNING state, process has stayed up for > than 1 seconds (startsecs)
2018-05-17 09:05:36,140 INFO success: superadmin entered RUNNING state, process has stayed up for > than 1 seconds (startsecs)
```


