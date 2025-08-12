#!/usr/bin/env python3
import random
import json

width = 250
height = 350

for minutes in range(0,60):
    for seconds in range(0,6):
        saliencesNumber = random.randint(40,50)
        saliences=[]
        for salience in range(saliencesNumber):
            saliences.append([random.randint(0,width),random.randint(0,height)])
        with open("{}-{}".format(minutes,seconds), "w") as f:
            json.dump(saliences, f)
