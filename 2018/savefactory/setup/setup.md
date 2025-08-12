# Building and running the docker:

First time: `make build`
Then, run: `make run`


`6666`: port number chosen to ssh to the container.

`6100-6300`: range of container's ports to be published and mapped to the range `6100-6300` of the host's ports.

If the host ports are not the same as the container ports then use `docker port` to check the actual ports' mapping.

To use a different range of ports, modify the challenge's server port in the `add_player.sh` script. 

# Adding a team:
Log in to the container as root:
```
~$ ssh root@"HOST_IP_ADDRESS" -p "SSH_PORT"
~$ cd /challenger/
~$ ./add_player.sh
```

The script will print out the username and the port at which the challenge's server will be listening.

The `.shadow` and `.used_ports` files will be automatically updated.

* `.shadow` contains a list of usernames, user number and their passwords in plain text.

```
USER_NUMBER 	USERNAME:PASSWORD
```

* `.used_ports` is there to track the teams' usernames and the assigned port number for the challenge.

Its lines follow a similar format.
```
USER_NUMBER 	USERNAME:PORT_NUMBER
```

## Google Instance:

- IP address: `35.233.31.82`
- Instance name: `instance-10`
- password: `th1sIsSometh1ngSecure`
- username: `hjaafar`

