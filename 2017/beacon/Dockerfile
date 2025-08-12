FROM node:latest

MAINTAINER @cryptax

# docker run -it --net=host --privileged --name bleno cryptax/bleno /bin/bash

# Install bleno and dependencies ---------------
# bluetooth IS required despite we do service bluetooth stop in the end
RUN DEBIAN_FRONTEND=noninteractive apt-get update && \
    apt-get install -yqq \
    build-essential \
    bluetooth \
    bluez \
    emacs \
    libbluetooth-dev \
    libudev-dev \
    usbutils \
    && rm -rf /var/lib/apt/lists/*		   

RUN npm install sandeepmistry/bleno
WORKDIR /root
ADD beacon-service.js /root/beacon-service.js
ADD beacon-characteristic.js /root/beacon-characteristic.js
ADD main.js /root/main.js

# Cleaning up and final setup -------------------------
RUN apt-get autoremove -yqq
RUN apt-get clean

RUN service bluetooth stop
CMD [ "node", "/root/main.js" ]





