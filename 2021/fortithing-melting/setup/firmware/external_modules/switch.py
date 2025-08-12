from machine import Pin 
import bias
import server
import utime
import os

try:
    import oled
except:
    print("Unable to import oled")

def get_temperature(p):
    print("Challenge Melting Point is running...")
    serv = server.get()	
    result = bias.get(serv)
    print(result)
    try:
    	oled.output(result)
    except:
	print("oled not found")

def start():
    if os.uname().sysname == 'esp32':
        sw3 = Pin(18, Pin.IN, Pin.PULL_UP) # btn button
        rst2 = Pin(0, Pin.IN, Pin.PULL_UP) # en button
    else:
        sw3 = Pin(13, Pin.IN, Pin.PULL_UP)
        rst2 = Pin(0, Pin.IN, Pin.PULL_UP)
    #rst2.irq(trigger=Pin.IRQ_FALLING, handler=run_TheSignal)
    sw3.irq(trigger=Pin.IRQ_FALLING, handler=get_temperature)
    


