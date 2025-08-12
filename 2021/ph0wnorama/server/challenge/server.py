from flask import Flask, flash, request, redirect, render_template
from gevent.pywsgi import WSGIServer
import math
import base64
import numpy as np
import datetime
import json
import os
from flask import send_from_directory
import sys


app = Flask(__name__)
app.secret_key = "7bh7hb7BHBBHB7bY7BYB7tyB7VTY7bYB7N7NYIMNYVRC5kbkiM"
app.config['MAX_CONTENT_LENGTH'] = 50 * 1024

flag="Ph0wn{G3omeTry_1s_4lway5_Us3Ful}"

def calculateDistance(a,b):
     dist = math.sqrt((a[0] - b[0])**2 + (a[1] - b[1])**2)
     return dist

@app.route('/favicon.ico')
def favicon():
    return send_from_directory(os.path.join(app.root_path, 'static'), 'images/favicon.ico')


@app.route('/', methods=['GET'])
def hello():
    return "Hello..."

@app.route('/', methods=['POST'])
def check_post():
    # try:
    if 'username' not in request.json:
        return "Missing user name"
    username = request.json["username"]

    if 'fingerprint' not in request.json:
        return "Missing user fingerprint"
    fingerprint = request.json["fingerprint"]

    #Loading saliency points
    database = {}
    database['alice'] = [[35.0, 112.0], [47.0, 110.0], [50.0, 79.0], [73.0, 165.0], [89.0, 208.0], [93.0, 107.0], [102.0, 187.0], [134.0, 114.0], [140.0, 148.0], [154.0, 83.0], [159.0, 70.0], [164.0, 178.0], [180.0, 131.0], [182.0, 98.0], [184.0, 111.0], [199.0, 116.0], [205.0, 70.0], [207.0, 138.0], [209.0, 94.0], [228.0, 187.0], [269.0, 190.0], [299.0, 144.0], [311.0, 192.0], [316.0, 146.0], [321.0, 179.0]]
    database['bob'] = [[25.0, 153.0], [36.0, 101.0], [38.0, 152.0], [44.0, 237.0], [50.0, 160.0], [53.0, 246.0], [57.0, 30.0], [57.0, 68.0], [65.0, 24.0], [74.0, 18.0], [79.0, 129.0], [79.0, 245.0], [85.0, 234.0], [88.0, 160.0], [94.0, 131.0], [96.0, 168.0], [100.0, 155.0], [108.0, 73.0], [109.0, 143.0], [115.0, 154.0], [127.0, 138.0], [135.0, 156.0], [149.0, 157.0], [157.0, 135.0], [176.0, 106.0], [182.0, 138.0], [184.0, 52.0], [221.0, 172.0], [248.0, 166.0], [252.0, 20.0], [254.0, 223.0], [259.0, 37.0], [264.0, 132.0], [265.0, 217.0], [268.0, 44.0], [270.0, 200.0], [274.0, 122.0], [291.0, 78.0], [296.0, 252.0], [298.0, 51.0], [303.0, 116.0], [307.0, 32.0], [311.0, 194.0], [318.0, 120.0], [323.0, 85.0], [327.0, 193.0]]
    minute = datetime.datetime.now().minute
    second = int((datetime.datetime.now().second)/10)
    filename = "adminSaliences/{}-{}".format(minute,second)
    with open(filename) as f:
        database['admin'] = json.load(f)

    if username not in database:
        return "Wrong username {}".format(str(username))
    

    userFingerprint = database[username]
    status, message, hint = compare(username,fingerprint,userFingerprint)
    if request.cookies.get('RGVidWdnaW5n') == 'VHJ1ZQ==':
        message+=" "+hint
    return message

    # except:
    #     return "Error in processing"

def compare(username,userSupplied, stored):
    if len(userSupplied) != len(stored):
        return False,"Login Failed","You need {} salient points".format(len(stored))
    else:
        totalDistance=0
        hint=""
        for userSuppliedPoint,storedPoint in zip(userSupplied,stored):
            currentDistance = calculateDistance(userSuppliedPoint, storedPoint)
            hint+=str(currentDistance)+";"
            totalDistance+=currentDistance
        if totalDistance<=1:
            if username=='admin':
                hint=flag
            else:
                hint='...btw no Flag here'
            return True, "Successfully logged in", hint
        else:
            return False, "Login Failed", hint

if __name__ == "__main__":
    http_server = WSGIServer(('', 1234), app)
    http_server.serve_forever()
