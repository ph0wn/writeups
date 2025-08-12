import wifi
import credentials
import switch
try:
    import oled
except:
    print("Unable to import oled")

try:
    oled.output("Booting...")
    oled.output_row("Trying to", 2)
    oled.output_row("connect to wifi...", 3)
except:
    print("oled not found")
ssid, pw = credentials.get()
wlan = wifi.connect(ssid, pw)
if wlan.isconnected():
    print("wifi connected")
else:
    print("Error: cannot connect to wifi")
try:
    oled.start(wlan.isconnected())
except:
    print("oled not found")
switch.start()
