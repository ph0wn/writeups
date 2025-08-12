#!/usr/bin/env python

import random

words = [ 'robot', 'ev3', 'linux', 'lego', 'ludoze', 'bricks', 'TelecomParistech', 'armstrong', 'embedded', 's0smart', 'IoT', 'congrats', 'we11done', 'hurray', 'bravo', 'U_did_It', 'expert', 'success', 'g00d', 'CruleZ', 'drinkSoda', 'drinkUrOrangina', 'Thirsty_for_7up', 'Wanna_coke', 'H0pe_U_enjoyed', 'Is_it_still_cold', 'U_deserve_a_drink', 'WeRc00l' ]

def setup():
    random.seed(1974)

def generate():
    max = len(words)
    n1 = random.randint(0,9999)
    n2 = random.randint(0,max-1)
    return "ph0wn{%s%04d}" % (words[n2], n1)
    	  
if __name__ == "__main__":
    setup()
    print "Generating passwords for ev3robot..."
    for i in range(0, 50):
        print generate()

