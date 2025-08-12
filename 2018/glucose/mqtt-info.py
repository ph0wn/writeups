#!/usr/bin/env python

import paho.mqtt.client as mqtt
from time import sleep
import logging

# Logging
logging.basicConfig(filename='/dev/stdout', format='[%(name)s] %(asctime)s %(message)s', datefmt='%m/%d/%Y %I:%M:%S', level=logging.DEBUG)
logger = logging.getLogger('info')
logger.debug("Logging configured")

# MQTT setup
client = mqtt.Client('info') # client id should be unique
client.username_pw_set(username='superadmin', password='Th1sOneMustBeVerySecuRe@')
client.connect("127.0.0.1", 1883, 60)
logger.info("Connected as superadmin")
client.subscribe("ph0wn18/#")
logger.info("Subscribed to all topics")

while True:
    client.publish("ph0wn18/info", "connect as admin for more topics")
    logger.info("Published to ph0wn18/info")
    sleep(60)
