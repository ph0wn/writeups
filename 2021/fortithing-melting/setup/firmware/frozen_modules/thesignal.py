from machine import Pin, PWM
import time
import ob
try:
    import oled
except:
    print("Unable to import oled")

p1 = Pin(5, Pin.OUT) 		# OUTPUT: Pin 1 on the FortiThing board
p2 = Pin(4, Pin.IN) 		# INPUT: Pin 2 on the FortiThing board

def signalChallenge():

    cipher = {}
    # plain text "..-....---.---.---.---..-"
    cipher['text'] = b'\xea\xb3\x96i\x93\xfak\xf8c\x1f\xfc\xb9\x1bGC\xae\x9d\xbc\xa8G\xde\x19cTnD\xf6T\xb2\xbf\xeck'
    cipher['length'] = 25

    mys = ob.decrypt(cipher)

    # plain text "_y0u_c4pTur3d_tH3_M0rs3_m3ss4g3"
    cipher['text'] = b'\xc8\xa0\x1e\xacN\x7f\xa4\x16wo7b\xd2\x8d\xfe\xa1\x1bb]\xbb\xc8\x84V\xaf\xb6S\x03\xadi\x1e@\xf7'
    cipher['length'] = 31
    f_mys = ob.decrypt(cipher)

    i=0
    j=0
    freq=4.0			# frequency factor
    div_test=1.0			# JUST FOR TESTING: factor to implement the software frequency division
    dash=2/(div_test*freq)		# length of a dash-> 1 
    dot=1/(3*div_test*freq)		# length of a point-> 0
    pause=1/(10*div_test*freq)	# length of a pause between 1 and 0 in the same character
    space=3/(2*div_test*freq)	# length of the space between to consecutive characters
    char_ok=0			# number of characters on the input pin with the correct frequency
    n_str=0				# number of characters checked by the frequency checker
    freq_str=""			# string which memorizes the pattern corresponding the a character
    while(True):
    	# Initialize the led status to OFF - The on/off is needed to 
    	# turn off the led on the output of the frequency divider
    	p1.on()
    	p1.off()
    	p1.on()
	p1.off()

	time.sleep(1) # wait a second

	for i in mys:		        # loop on the string to transmit
		freq_str=""		# initialize the pattern of a character to empty
		if(i=="-"): 	        # if the character of the signal is a dash->1
			n_str+=1	# increment the number of character in the signal
	       
			# the pulse has to be transmitted a number of time equal to freq
			# this is necessary since a hardware frequency divider is used
			for n in range(0,freq): 
				p1.on()	        		# set the output to HIGH->1
				time.sleep(dash)		# keep the output HIGH for a dash time
				freq_str+=str(p2.value())	# add the current value of the input pin
        			    				#    to the pattern used by the frequency checker
				p1.off()			# set the output to LOW->0
				time.sleep(pause) 		# wait the pause time
				freq_str+=str(p2.value())	# add the current value of the input pin
			
			# FREQUENCY CHECKER: check if the generated pattern (corresponding to the transmitted character)
			# has the correct frequency checking if it contains 0000 or/and 1111
			if((freq_str.find("0000")>=0 and freq_str.count("0000")==1) or 
				(freq_str.find("1111")>=1 and freq_str.count("1111")==1)):
				char_ok+=1		# increment the number of characters with the correct frequency
				freq_str=""		# re-initialize the character pattern to empty
		    
	    	elif(i=="."):	        # if the character of the signal is a point->0
			n_str+=1	# increment the number of character in the signal
			
			# the pulse has to be transmitted a number of time equal to freq
			# this is necessary since a hardware frequency divider is used
			for n in range(0,freq):
				p1.on()		        	# set the output to HIGH->1
				time.sleep(dot)		        # keep the output HIGH for a dot time
				freq_str+=str(p2.value())	# add the current value of the input pin
	        		    				#    to the pattern used by the frequency checker
				p1.off()			# set the output to LOW->0
				time.sleep(pause)		# wait the pause time
				freq_str+=str(p2.value())	# add the current value of the input pin
	    
				# FREQUENCY CHECKER: check if the generated pattern (corresponding to the transmitted character)
				# has the correct frequency checking if it contains 0000 or/and 1111
				if((freq_str.find("0000")>=0 and freq_str.count("0000")==1) or 
					(freq_str.find("1111")>=1 and freq_str.count("1111")==1)):
					char_ok+=1		# increment the number of characters with the correct frequency
					freq_str=""		# re-initialize the character pattern to empty
			    

	# if the number of characters with a correct input frequency is equal to
	# the number of characters in the transmitted string, print the last part of the flag
	if(char_ok==n_str):
		print("Good job! The last part of the flag is: "+f_mys)
		print("\n Sending the signal again...\n")
	else:
		print("The input signal is not correct! Pay attention to the frequency!")
		print("\n Sending the signal again...\n")	

	# initialization for the next iteration
	char_ok=0
	n_str=0
	freq_str=""

	# wait 5 seconds before the next signal transmission
	time.sleep(5)
		
	# turn the led ON to be synchronized with the beginning of the loop where the led is turned OFF
	p1.on()
	p1.off()
	p1.on()
	p1.off()
	
