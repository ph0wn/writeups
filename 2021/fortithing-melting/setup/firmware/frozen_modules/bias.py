import http_requests
#import ure
import ujson
import machine
from machine import Pin, I2C
import bme280_float
import utime as time
import credentials
import ob
try:
    import oled
except:
    print("Unable to import oled")

def get(server):
    url = 'http://' + server  
    path = {}
    path['text'] = b'\x19P\xc8\x10\xbfj\xf0{\xd1C\xb1\x8bq\xb1ppA\x86\xc5\xe1\xacm\x12|2\xefq\xb9\x0e\xfb\xa6\x8dxe\xd8\xa4\xf6_"\x12\x1cw\xb5\xa6I\xe4\x9d\x8e\x90xYf\x12\x0f\x99\x13\xde\x1c\x1dC\x19C\x8a\x84'
    path['length'] = 59
    path = ob.decrypt(path)
    url += path
    iron_melting_point = 1538.0
    flag = {}
    flag['text'] = b"'\x92\xcc\xa4\x89\xdc\xbe\x1f\xd5`\xc1$\xe0\xa1\x9d\xac\xb2\x9a%\xec/]\xa0\xcbt\xab\x8e\xe7\xed\xc8\x17\xba\x8f\x9d\xe9\xa8\x89\xea\x1f\xa3\x17\xf1\x8bw\xd8'\x01'\xf7\x12q\xaf\xbe\xe2\xe1\x1d\xf8\xc2h\xe9q7\\&"
    flag['length'] = 55
    flag = ob.decrypt(flag)
    port = 8080
    env_temp = 25.0
    correct_ssid = credentials.check_ssid()
    if not correct_ssid == True:
        if correct_ssid[1] == "ERR_CONN":
            return "No internet connection"
        else:
            ssid, _ = credentials.get()
            return "SSID not correct. Connect to " + ssid + " and retry."
    print("Contacting the server...")
    try:
        oled.output("Contacting the  server...")
    except:
        print("oled not found")
    try:
    	data = http_requests.http_get(url, port)
    except:
    	return "Error in server connection"
    ret = "It's too cold!"
    data = data.split('\r\n\r\n')[-1]
    try:
        data = ujson.loads(data)
    except:
        return "Error in request response"
    time.sleep(1)
    print("Getting the bias...")
    try:
        oled.output_row("Getting the bias", 2)
    except:
        print("oled not found")
    bias = float(data['bias'])
    time.sleep(1)
    print("Bias correctly obtained!")
    try:
        oled.output_row("Bias obtained!", 3)
    except:
        print("oled not found")
    time.sleep(1)
    
    print("Calibrating the sensor, do not touch it")
    try:
    	oled.output_row("Calibrating the", 4)
    	oled.output_row("sensor...", 5)
    	oled.output_row("Do not touch it", 6)
    except:
    	print("oled not found")
    i2c = I2C(scl=Pin(5), sda=Pin(4), freq=400000)
    bme = bme280_float.BME280(i2c=i2c)

    t0 = time.time()
    values = []
    while (time.time() - t0) < 3:    
    	values.append(float(bme.values[0][:-1]))
    env_temp = sum(values)/len(values)
    print("Sensor calibrated: " + str(env_temp))
    try:
    	oled.output_row('Completed.', 7)
    	oled.scrollDown_and_print('Value: ' + str(env_temp))
    except:
        print('oled not found')
    if bias > (iron_melting_point - env_temp - 1):
        bias = iron_melting_point - env_temp - 1
        print("Bias too high (total temperature cannot reach iron melting point). Normalized to " + str(bias))
        try:
            oled.scrollDown_and_print("Bias too high.")
            oled.scrollDown_and_print("Normalized to:")
            oled.scrollDown_and_print(str(bias))
        except:
            print("oled not found")
    challenge = data['challenge']
    if challenge != '3824A6FE2C828':
        ret = 'Who are you?'
        return ret
    
    t0 = time.time()
    time.sleep(3)
    while (time.time() - t0) < 60:
        actual_temp = float(bme.values[0][:-1])
        temp = actual_temp + bias
        print("Temp: " + str(actual_temp) + ", Bias: " + str (bias) + ", Total: " + str(temp)+"C")
        try:
        	oled.scrollDown_and_print("Temp: "+str(temp)+"C")
        except:
        	print("oled not found")
        if temp > iron_melting_point:
            ret = flag
        time.sleep(3)
    return ret






