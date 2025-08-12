#!/usr/bin/env python3

import requests
import json
import base64
import re
import random
import math

def intersect(p0, r0, p1, r1):
    x0 = p0[0]
    y0 = p0[1]
    x1 = p1[0]
    y1 = p1[1]
    d=math.sqrt((x1-x0)**2 + (y1-y0)**2)

    # non intersecting
    if d > r0 + r1 :
        return None
    # One circle within other
    if d < abs(r0-r1):
        return None
    # coincident circles
    if d == 0 and r0 == r1:
        return None
    else:
        a=(r0**2-r1**2+d**2)/(2*d)
        h=math.sqrt(r0**2-a**2)
        x2=x0+a*(x1-x0)/d   
        y2=y0+a*(y1-y0)/d   
        x3=x2+h*(y1-y0)/d     
        y3=y2-h*(x1-x0)/d 

        x4=x2-h*(y1-y0)/d
        y4=y2+h*(x1-x0)/d

        return [x3, y3], [x4, y4]

url = "http://localhost:1234"
username = 'admin'
saliencies1 = []
saliencies2 = []
cookies={'RGVidWdnaW5n':'VHJ1ZQ=='}
#1st request: how many points do we need?
req = requests.post(url=url,json={"username":username,"fingerprint":[]},cookies=cookies)
assert req.status_code == 200, "1st req: error in processing\n"+req.text
try:
    howMany = int(re.search('need\ ([0-9]+)',req.text).group(1))
    for salience in range(howMany):
        saliencies1.append([random.randint(0,500),random.randint(0,500)])
        saliencies2.append([random.randint(0,500),random.randint(0,500)])
    #2nd request: first series of distances
    req = requests.post(url=url,json={"username":username,"fingerprint":saliencies1},cookies=cookies)
    assert req.status_code == 200, "2nd req: error in processing\n"+req.text
    distances1 = req.text.replace("Login Failed ","").split(';')[:-1]
    #3rd request: second series of distances
    req = requests.post(url=url,json={"username":username,"fingerprint":saliencies2},cookies=cookies)
    assert req.status_code == 200, "3rd req: error in processing\n"+req.text
    distances2 = req.text.replace("Login Failed ","").split(';')[:-1]
    intersections1, intersections2 = [], []
    for p0, r0, p1, r1 in zip(saliencies1,distances1,saliencies2,distances2):
        intersection1, intersection2 = intersect(p0,float(r0),p1,float(r1))
        if intersection1 is None or intersection2 is None:
            raise Exception('Problem with intersections')
        intersections1.append(intersection1)
        intersections2.append(intersection2)
    #4th request: which points are correct?
    req = requests.post(url=url,json={"username":username,"fingerprint":intersections1},cookies=cookies)
    assert req.status_code == 200, "4th req: error in processing\n"+req.text
    distances3 = req.text.replace("Login Failed ","").split(';')[:-1]
    finalSaliences = []
    for index,distance in enumerate(distances3):
        if float(distance)<0.1:
            finalSaliences.append(intersections1[index])
        else:
            finalSaliences.append(intersections2[index])
    #5th request: get the flag
    req = requests.post(url=url,json={"username":username,"fingerprint":finalSaliences},cookies=cookies)
    assert req.status_code == 200, "5th req: error in processing\n"+req.text
    print(req.text)
except Exception as e:
    print(e)
