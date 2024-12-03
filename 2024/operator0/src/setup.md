# Web

## Requirements
- Docker
- Docker-compose

## Setup
copy the folder web to your webserver and run the following command

```bash
cd web
bash startApp.sh
```
this will build the docker image and make the app available on port 8080.
if the port is already in use you can change it by editing the docker-compose.yml file.



# Raspberry Pi Setup

## Requirements 
- Raspberry Pi >= 3 



## Setup 
scp the files to your pi home directory:

- raspberryPi/setupChallengeRaspberry.sh
- raspberryPi/injectorMalware/compile.sh

```bash
scp raspberryPi/setupChallengeRaspberry.sh pi@<ip>:~/Desktop
scp raspberryPi/injectorMalware/compile.sh pi@<ip>:~/Desktop
```

## Compile and Install

### Challenge user creation with necessary packages and configuration
***Note:***: the script requires sudo privileges to run

```bash
cd ~/Desktop
sudo bash setupChallengeRaspberry.sh userSetup
```

### Malware injector compilation

```bash
cd ~/Desktop
sudo bash compile.sh
```
before starting the injector, make sure that the service ssh is running on the raspberry pi.
the script will :
    - compile the injector
    - create a shared library called payload.so and a binary called ntp 
    - copy the shared library to /tmp and the binary /usr/bin/  

after the compilation, you can start the injector by running the following command in background mode: 

```bash
sudo ntp $(ps aux | grep sshd | grep -v grep | awk '{print $2}') &
```


# Network Setup
## Part 1 - Web APP
- The web app will be running on port 9001
- Traffic type allowed for the web app is HTTP 

## Part 2 - Raspberry Pi
- The raspberry will need to have a static IP address
- The raspberry pi will be running an SSH service on port 22
- Traffic type allowed for the raspberry pi is SSH

