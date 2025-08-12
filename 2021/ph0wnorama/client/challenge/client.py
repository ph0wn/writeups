#!/usr/bin/env python3
import cv2
import numpy as np
import skimage.morphology
import skimage
import base64
import json
from getTerminationBifurcation import getTerminationBifurcation;
from removeSpuriousMinutiae import removeSpuriousMinutiae
import requests
from flask import Flask, flash, request, redirect, render_template, make_response
from gevent.pywsgi import WSGIServer

app = Flask(__name__)

#You don't need more than 50 Kb
app.config['MAX_CONTENT_LENGTH'] = 50 * 1024
app.secret_key = "d87UHBIy7I8IHjiBNBIHkbhjbkhkjbhKUBKN"

@app.route('/robots.txt')
def robots():
    return "static/images/alice.jpg<br>static/images/bob.jpg<br><br>YWRtaW4gcGFzc3dvcmQgY2hhbmdlcyBldmVyeSB0ZW4gc2Vjb25kcw=="

@app.route('/')
def client_welcome():
    response = make_response(render_template('client.html'))
    response.set_cookie('RGVidWdnaW5n', 'RmFsc2U=')
    return response

@app.errorhandler(413)
def request_entity_too_large(error):
    flash('Hey, you can upload 50 Kb maximum!')
    return render_template('client.html'), 413

@app.route('/', methods=['POST'])
def upload_fingerprint():
    if request.method == 'POST':
        #We need a username
        if 'username' not in request.form:
            flash('Hey, what username do you want to log in with?')
            return redirect(request.url)
        username = request.form['username']
        if len(username) ==0 :
            flash('Hey, what username do you want to log in with?')
            return redirect(request.url)
        #We need a fingerprint and a file
        if 'fingerprint' not in request.files:
            flash('Hey, provide a fingerprint image!')
            return redirect(request.url)
        fingerprint = request.files['fingerprint']
        if fingerprint.filename == '':
            flash('Hey, provide a fingerprint image!')
            return redirect(request.url)
        #We need the correct extensions
        if fingerprint and allowedImages(fingerprint.filename):
            saliencies= getSaliency(fingerprint)
            #We need a fingerprint here
            if saliencies is None:
                flash("Your image has got something weird!")
                return redirect(request.url)
            #It seems that we have some interesting points
            payload = {"username":username,"fingerprint":saliencies}
            resp = requests.post(url="http://server:1234",json=payload,cookies=request.cookies)
            flash(resp.text)
            return redirect(request.url)
        else:
            flash('Hey, only png, jpg, jpeg, gif')
            return redirect(request.url)


#Function definitions
def allowedImages(filename):
    ALLOWED_EXTENSIONS = set(['png', 'jpg', 'jpeg', 'gif'])
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

def getSaliency(fingerprint):
    try:
        saliencies = []
        img = cv2.imdecode(np.frombuffer(fingerprint.read(), np.uint8),0);
        img = np.uint8(img>128);
        skel = skimage.morphology.skeletonize(img)
        skel = np.uint8(skel)*255;
        mask = img*255;
        (minutiaeTerm, _) = getTerminationBifurcation(skel, mask);
        minutiaeTerm = skimage.measure.label(minutiaeTerm, connectivity=2);
        RP = skimage.measure.regionprops(minutiaeTerm)
        minutiaeTerm = removeSpuriousMinutiae(RP, np.uint8(img), 10);
        TermLabel = skimage.measure.label(minutiaeTerm, connectivity=2);
        RP = skimage.measure.regionprops(TermLabel)
        for i in RP:
            row, col = np.round(i['Centroid'])
            saliencies.append([row,col])
        return saliencies
    except Exception:
        saliencies=None
    finally:
        return saliencies

if __name__ == "__main__":
    http_server = WSGIServer(('', 1233), app)
    http_server.serve_forever()
