#!/usr/bin/env python3

drug_list = [ "Aspirin","Bromocriptine","Codeine","Desloratadine","Estradiol","Fluoxtenie","Glycerin","Heparin","Insulin","Lidocaine","Methadone","Nasonex","0mega","Paracetamol","Relafen","Singulair","Topamax",	"Vicodin","Wynzora", "+Solupred" ]

flag = 'PH0WN{BEWARE+THE+NEEDLES}'

def find_index(c):
    for i in range(0, len(drug_list)):
        if drug_list[i][0].lower() == c.lower():
            return i

    raise Error("We couldnt find c=",c)


address = ''
multiplier = 0
print("Len drug_list={}".format(len(drug_list)))
print("Len flag={}".format(len(flag)))
for i in range(0, len(flag)):
    if flag[i] == '{' or flag[i] == '}':
        address = address + '::'
        print("flag[{}]={} address={}".format(i, flag[i], address))
        continue

    index = find_index(flag[i])
    multiplied = index + (len(drug_list) * multiplier)
    if multiplied > 255:
        multiplier = 0
        multiplied = index
    address = address + '{:02x}'.format(multiplied)
    print("flag[{}]={} -> drug[{}]={} multiplier={} multiplied={} (0x{:02x}) address={}".format(i, flag[i], index, drug_list[index], multiplier, multiplied, multiplied, address))
    
    multiplier = multiplier + 1
    if multiplier > 4:
        multiplier = 1

print(address)

    
