import network
import ubinascii
import utime

def connect(ap, pw):
    max_sec = 10
    SSID = ap
    password = pw
    wlan = network.WLAN(network.STA_IF)
    wlan.active(True)
    t0 = utime.time()
    if not wlan.isconnected():
        print('Connecting to network...')
        #wlan.ifconfig(('192.168.1.201', '255.255.255.0', '192.168.1.254', '8.8.8.8'))
        wlan.connect(SSID, password)
        while not wlan.isconnected() and (utime.time()-t0) < max_sec:
            #print("Unable to connect to ", SSID)
           # wlan.active(False)
            pass;
    if wlan.isconnected():
        print('network config: ', wlan.ifconfig())
    else:
        print('Unable to connect!')
        wlan.active(False)
    return wlan

def disconnect():
    wlan = network.WLAN(network.STA_IF)
    if wlan.isconnected():
        wlan.active(False)
    print("Wifi disconnected!")
    return wlan



