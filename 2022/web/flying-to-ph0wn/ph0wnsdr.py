import requests
import json
import time

url = 'http://10.210.17.162:8080/dump/data.json'

altitude_msg = []
speed_msg = []

for i in range(0,500):
    document = requests.get(url).json()
    for plane in document:
        if plane['flight'] == "AIRPH0":
            altitude = plane['altitude']
            if len(altitude_msg) > 0:
                if altitude != altitude_msg[-1]:
                    altitude_msg.append(altitude)
            else: 
                altitude_msg.append(altitude)

            msg = ''.join([chr(j) for j in altitude_msg])
            print("altitude msg={0}".format(msg))
            #print('altitude={0} ({1})'.format(altitude, chr(altitude)))

        if plane['flight'] == "EZYPH0":
            speed = plane['speed']
            if len(speed_msg) > 0:
                if speed != speed_msg[-1]:
                    speed_msg.append(speed)
            else: 
                speed_msg.append(speed)
            msg = ''.join([chr(j) for j in speed_msg])
            print("speed msg={0}".format(msg))
            #print('speed={0} ({1})'.format(speed, chr(speed)))
            
    #time.sleep(0.01)
    print("---")

        
        
