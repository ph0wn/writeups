FROM eclipse-mosquitto:1.4.12
MAINTAINER Axelle Apvrille <aapvrille@fortinet.com>
LABEL Description="MQTT CTF challenge"

RUN apk --no-cache add --update supervisor python python-dev py-pip build-base && \
    mkdir -p /var/log/supervisor && \
    mkdir -p /etc/supervisor/conf.d && \
    pip install --upgrade pip && \
    pip install paho-mqtt && \
    pip install pycrypto && \
    rm -rf /var/cache/apk/*
    
ADD supervisord.conf /etc/supervisor/conf.d/supervisord.conf
ADD mosquitto.conf /mosquitto/config/mosquitto.conf
ADD mosquitto.pwd /mosquitto/config/mosquitto.pwd
ADD mosquitto.acl /mosquitto/config/mosquitto.acl
ADD mqtt-admin.py /root/mqtt-admin.py
ADD mqtt-superadmin.py /root/mqtt-superadmin.py
ADD mqtt-info.py /root/mqtt-info.py

CMD [ "/usr/bin/supervisord", "-c",  "/etc/supervisor/conf.d/supervisord.conf" ]
