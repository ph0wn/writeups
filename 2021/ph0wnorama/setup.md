- Install on a remote host `server` and `client` directories: `scp -r client server USER@REMOTEHOST:./biometric`
- Launch the containers: `docker-compose up -d`

The client container contains a website on port 1233 that CTF players access to login Ph0worama bank.
The server container contains the flag. This one runs on port 1234, and must be accessible to CTF players, and to the other container.


