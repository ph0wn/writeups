from machine import Pin, SoftI2C
from ssd1306 import SSD1306_I2C
import math
import time

def title(oled):
    oled.fill(0)
    oled.text("Super", 47, 0)
    oled.text("Shitty", 46, 16)
    oled.text("Add-On", 46, 32)
    oled.show()
    print("title() done")


def rose8(oled, cx=64, cy=32, r_long=24, r_short=14):
    oled.fill(0)

    # Cercle léger (optionnel)
    for a in range(0, 360, 10):
        x = cx + int(r_long * math.cos(math.radians(a)))
        y = cy + int(r_long * math.sin(math.radians(a)))
        oled.pixel(x, y, 1)

    # 8 rayons + petites pointes
    for i in range(8):
        ang = i * 45
        r = r_long if (i % 2 == 0) else r_short  # N/E/S/W plus longs
        x1 = cx + int(r * math.cos(math.radians(ang)))
        y1 = cy + int(r * math.sin(math.radians(ang)))
        oled.line(cx, cy, x1, y1, 1)

        # Pointe en "V"
        a1 = ang + 12
        a2 = ang - 12
        xb1 = cx + int((r - 6) * math.cos(math.radians(a1)))
        yb1 = cy + int((r - 6) * math.sin(math.radians(a1)))
        xb2 = cx + int((r - 6) * math.cos(math.radians(a2)))
        yb2 = cy + int((r - 6) * math.sin(math.radians(a2)))
        oled.line(x1, y1, xb1, yb1, 1)
        oled.line(x1, y1, xb2, yb2, 1)

    # Lettres
    oled.text("N", cx-3, cy-r_long-10)
    oled.text("S", cx-3, cy+r_long+2)
    oled.text("W", cx-r_long-10, cy-4)
    oled.text("E", cx+r_long+2, cy-4)

    oled.show()
    print("rose8() done")

def oled_display():
    WIDTH=128
    HEIGHT=64
    i2c = SoftI2C(scl=Pin(21), sda=Pin(20), freq=400000)
    oled = SSD1306_I2C(WIDTH, HEIGHT, i2c)

    title(oled)
    time.sleep(3)
    rose8(oled)
    time.sleep(5)

    oled.fill(0)
    oled.text("Bearing N West", 0, 0)
    oled.text("wo7~u\\x82ZZHVf", 0, 16)
    oled.text("pzf{o:ful~f8u{", 0, 30)
    oled.text("lymGjl\\x84\\x11", 0, 44)
    oled.show()

    print("oled_display() done")
    pass
