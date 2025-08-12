#!/usr/bin/env python

import random

words = [ 'picoWell', 'ph0wn', 'sophiaAntipolis', 'antipolis', 'fortinet', 'IoThings', 'starstar', 'pacman', 'network', 'security', 'research', 'hack', 'CTF', 'framework', 'java', 'python', 'ethernet', 'android', 'toothbrush', 'glucose', 'meter', 'medical', 'chocolate', 'banana', 'apple', 'juice', 'vegetable', 'goat', 'snake', 'crocodile', 'cat', 'dog', 'camel', 'frog', 'alligator', 'duck', 'donald', 'wallace', 'gromit', 'crackers', 'james', 'bond', 'laptop', 'windows', 'linux', 'mint', 'ubuntu', 'xubuntu', 'fsf', 'snowden', 'redhat', 'blackhat', 'fedora', 'core', 'spectre', 'meltdown', 'uranus', 'mac', 'pluto', 'mars', 'moon', 'sun', 'oracle', 'peach', 'blue', 'red', 'green', 'ecology', 'warming', 'Earth', 'avatar', 'bruce', 'batman', 'catwoman', 'hoodie', 'virus', 'malware', 'cyber', 'fortigate', 'fortimail', 'fortianalyzer', 'fortitoken', 'fortiswitch', 'fortiwifi', 'fortiap', 'smart', 'boss', 'manager', 'ala1n', 'cryptax', 'ludov1c', 'phil', 'fabr1ce', 'fabien', 'fablab', 'query', 'aix', 'hpux', 'pollux', 'sparc', 'ios', 'blackberry', 'symbian', 'eurecom', 'university', 'felicity', 'matrix', 'pill', 'challenge', 'meal', 'register', 'hotel', 'sea', 'mercantour', 'jm2l', 'France', 'Europe', 'euro', 'football', 'soccer', 'swim', 'dive', 'ski', 'snow', 'Grenoble', 'GreHack', 'BlackAlps', 'hardwear', 'laboratory', 'cool', 'excited', 'amazing', 'suspect', 'wonderful', 'want', 'attempt', 'score', 'board', 'Lerins', 'Cannes', 'Antibes', 'Valbonne', 'Biot', 'bicycle', 'car', 'horse', 'ride', 'paint', 'draw', 'eat', 'play', 'go', 'win', 'copy', 'dream', 'sleep', 'glasses', 'connected', 'wifi', 'cable', 'citrus', 'vegan', 'tofu', 'carrot', 'weeds', 'skin', 'sound', 'mozart', 'violin', 'oboe', 'guitar', 'hero', 'oculus', 'harry', 'potter', 'magician', 'wizard', 'school', 'worry', 'hurry', 'your', 'my', 'one', 'two', 'three', 'four', 'flower', 'lavender', 'olive', 'pool', 'crypto', 'wallet', 'blog', 'waiter', 'plane', 'fly', 'ticket', 'television', 'movie', 'throne', 'game', 'maze', 'runner', 'disney', 'kylo', 'luke', 'skywalker', 'darth', 'Rey', 'casino', 'dollar', 'park', 'restaurant', 'fish', 'onion', 'tomato', 'pepper', 'salt', 'pumpkin', 'potato', 'salad', 'dressing', 'soja', 'coco', 'pizza', 'cheese', 'cheddar', 'comte', 'camembert', 'marcellin', 'Savoie', 'hurricane', 'water', 'wine', 'Bordeaux', 'roquefort', 'gorgonzola', 'nectaire', 'munster', 'brie' ]

def setup():
    random.seed(2945)

def generate(teamid):
    max = len(words)
    f = random.randint(0,4)
    n1 = random.randint(0,max-1)
    n2 = random.randint(0,max-1)
    n3 = random.randint(0,max-1)
    n = random.randint(0,9)
    #print "n1=%d, n2=%d, n3=%d, n=%d" % (n1, n2, n3, n)
    w1 = words[n1]
    w2 = words[n2]
    w3 = words[n3]
    if f == 0:
        password = "%s%d_%s" % (w1,n,w2)
        while len(password) < 16:
            password = password + '0'

        return password[0:16]
    if f == 1:
        password = "%s_%s_%s" % (w1,w2,w3)
        while len(password) < 16:
            password = password + '0'

        return password[0:16]
    if f == 2:
        password = "%s@%d%s" % (w1, n, w2)
        while len(password) < 16:
            password = password + '0'
        return password[0:16]

    password = "%s@%s_%d" % (w1, w2, n)
    while len(password) < 16:
        password = password + '0'
        
    return password[0:16]

if __name__ == "__main__":
    setup()
    l = []
    for id in range(0, 100):
        password = generate(id)
        print('teamid=%d password=%s len=%d' % (id, password,len(password)))
        l.append(password)

    print l
        
        
        
        
