minuscule = { }
i = 0
for mychar in range(ord('a'), ord('z')+1):
    minuscule[chr(mychar)] = i
    i = i +1
    
word = 'ph0wn{8c14d876907759e705b9f0e0eacfce1e}'

for i in range(0, len(word)):
    if word[i] in minuscule:
        print "pass[%d] = minuscule[%d];" % (i, minuscule[word[i]])
    if word[i] in list('0123456789'):
        print "pass[%d] = chiffre[%s];" % (i, word[i])
    if word[i] == '{':
        print "pass[%d]= signe[0];" % (i)
    if word[i] == '}':
        print "pass[%d]= signe[1];" % (i)
