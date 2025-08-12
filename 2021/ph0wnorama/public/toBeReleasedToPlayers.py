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
app.secret_key = "REDACTED"

@app.route('/', methods=['POST'])
def upload_fingerprint():
    if request.method == 'POST':
        #...
        #...
        #...
        #Some stuff going on here
        #...
        #...
        #...
        saliencies= getSaliency(fingerprint)
        payload = {"username":username,"fingerprint":saliencies}
        resp = requests.post(url="http://server:1234",json=payload,cookies=request.cookies)
        return redirect(request.url)


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
