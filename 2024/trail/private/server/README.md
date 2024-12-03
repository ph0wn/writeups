# Setting up the SERVER for the CTF

## Changes to the architecture

- The container is named `trail-server`
- In the `Dockerfile`, I removed several unnecessary packages such as python and nano.
- I do not share a directory with the host (`shared`). At startup, this results in a "fail" at mount time because it apparently tries to mount `/home/r0/shared`, but this is just a warning and it continues

## Port

We want the docker container to list on port 9123

```
   PORTFWD="9123:8080"
```

Do not mix this with the port string.

## Edit the flag

Start up the server instance of the container. Attach a shell to the container using the same steps as described above. Become root using `sudo -s` (the password is `r0`). Edit the file `/FLAG` and set the contents to whatever you want. The changes are persistent. If the volume `ph0wnvol` is rebuilt, then the `/FLAG` will be reset and has to be changed again.

