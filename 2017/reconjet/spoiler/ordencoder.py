text = 'Congratulations, this is stage 2 !\nTo flag this stage, flag is: Ph0wn{KKKPicoIsEverywhere} where you must replace KKK with the key you found during stage 1.\nKey for stage 3 is: c_Hqopef91\n\nStage 3:\nSearch in the room, direction NW, for a hidden treasure :) Be quiet so that other teams do not understand what you are doing.\nTo flag this stage, flag format is: Ph0wn{KKKKKKKKKKAAABBBCCCDDDEEEFFFGGG}.\nWhere KKKKKKKKKK is the key you found during stage 2.\n- AAA is the coordinates of the first lettter of word FORTINET is the treasure. First letter is column (A, B, C...). Second letter is row (1,2,3...). Third letter is word direction: H for horizontal, V for vertical. So, if FORTINET starts at row A, column 3, horizontally, we will have A3H.\n- BBB is the coordinates for TELECOM,\n- CCC for PARISTECH,\n- DDD for PLATEFORME,\n- EEE for CONCEPTION,\n- FFF for EURECOM\n- and GGG for GREHACK.\nGood luck.'

print "Encoding of explanations: "
print "-----------------------------------"
tab = [ ord(text[i]) for i in range(0, len(text)) ]
print tab

print "Decoding (to check)"
print "--------------------------"
decode = [ chr(x) for x in tab ]
print ''.join(decode)


