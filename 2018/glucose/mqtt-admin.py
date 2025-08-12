#!/usr/bin/env python

import paho.mqtt.client as mqtt
from time import sleep
from random import randint
import logging

logging.basicConfig(filename='/dev/stdout', format='[%(name)s] %(asctime)s %(message)s', datefmt='%m/%d/%Y %I:%M:%S', level=logging.DEBUG)
logger = logging.getLogger('admin')
logger.debug('Logger configured')

client = mqtt.Client('admin') # client id should be unique
client.username_pw_set(username='admin', password='Sugar1sBaaaaaaad')

client.connect("127.0.0.1", 1883, 60)
logger.info('Ready...')

while True:
    try:
        level = randint(60,150)
        client.publish("ph0wn18/glucose-level", level)
        logger.info("Published to ph0wn/glucose-level %d" % (level))
        sleep(20)
    except Exception, e:
        logger.error("Raised in while true loop", exc_info=True)
