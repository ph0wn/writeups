#!/usr/bin/env python3
import json
from flask import Flask, request, render_template, make_response, session
from flask_session import Session
import base64

app = Flask(__name__)
app.secret_key = "AAsUHBIy7I8IHjiBNBIHkbhjbkhkjbhKUBKN"
app.config['SESSION_TYPE'] = 'filesystem'
Session(app)

def initSession():
    if not session.get('upDown'):
        session['upDown'] = 0
    if not session.get('leftRight'):
        session['leftRight'] = 0

def makeResponse():
    filename = 'images/{}x{}'.format(session['upDown'],session['leftRight'])
    filename = filename.replace("-","N")
    with open(filename, "rb") as image_file:
        img_data = base64.b64encode(image_file.read())
        img_data = img_data.decode('utf-8')
    response = make_response(render_template('camera.html', img_data=img_data))
    return response

@app.route('/')
def home():
    initSession()
    return makeResponse()

@app.route('/down')
def down():
    initSession()
    session['upDown'] += 1
    session['upDown'] = min(session['upDown'],10)
    print('down')
    return makeResponse()

@app.route('/up')
def up():
    initSession()
    session['upDown'] -= 1
    session['upDown'] = max(session['upDown'],-10)
    print('up')
    return makeResponse()

@app.route('/right')
def right():
    initSession()
    session['leftRight'] += 1
    session['leftRight'] = min(session['leftRight'],10)
    print('right')
    return makeResponse()

@app.route('/left')
def left():
    initSession()
    session['leftRight'] -= 1
    session['leftRight'] = max(session['leftRight'],-10)
    print('left')
    return makeResponse()
