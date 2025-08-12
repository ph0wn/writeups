import ssd1306
from machine import Pin, I2C
import utime

i2c = I2C(scl=Pin(5), sda=Pin(4))
display = ssd1306.SSD1306_I2C(128, 64, i2c)

def start(wifi_status=True):
    display.fill(0)
    display.text("WELCOME", 36, 8)
    display.text("TO", 56, 24)
    display.text("PH0WN", 44, 40)
    display.show()

    utime.sleep(5)
    title = "The FortiThing"
    subtitle = "Choose challenge"
    chall_1 = "MeltingPoint:SW3"
    if wifi_status:
        wifi_line = "Connected to WiFi"
    else:
        wifi_line = "NOT CONNECTED!"
    display.fill(0)
    display.text(title, 0, 0)
    display.text(subtitle, 0, 8)
    display.text(chall_1, 0, 24)
    display.text(wifi_line, 0, 40)
    display.show()

def output(text):
    display.fill(0)
    if(len(text) > 128):
        print("Text too long. Only the first 128 chars will be printed")
    for i in range(0, 16*8, 16):
        if(i > len(text)):
            break
        display.text(text[i:i+16], 0, int(i/2))
    display.show()

def output_row(text, row):
    if row > 7:
        return
    display.fill_rect(0, 8*row, 128, 8, 0)
    if (len(text) > 16):
        print("Text too long. Only the first 16 chars will be printed")
    display.text(text, 0, row*8)
    display.show()

def scrollDown_and_print(text):
    display.scroll(0,-8)
    output_row(text, 7)
    
def signalOled():
    title = "Challenge:"
    chall = "The Signal"
    return_home = "Exit: RST1"
    display.fill(0)
    display.text(title, 0, 0)
    display.text(chall, 0, 8)
    display.text(return_home, 0, 56)
    display.show()

