'''
Skull Island Badge Firmware
High Security for Bandits!
'''
import badge_display
from machine import Pin, unique_id
from oled import oled_display
import time
import hashlib

UP = Pin(3, Pin.IN, Pin.PULL_UP) # BTN0 is SW1 = GPIO3
LEFT = Pin(5, Pin.IN, Pin.PULL_UP)
RIGHT = Pin(4, Pin.IN, Pin.PULL_UP)
DOWN = Pin(6, Pin.IN, Pin.PULL_UP)
EYE = Pin(14, Pin.OUT)
BADGE_ID = unique_id().hex()
VERSION = "2.1"

def flash_eye_of_pico():
    # Pico's eye flashes in delight
    EYE.value(0)
    time.sleep_ms(60)
    EYE.value(1)

# No 0/O/1/I, because pirates have only 1 eye
ALPH = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"

def read_challenge():
    challenge = ''
    while len(challenge) < 12:
        if not LEFT.value():
            print("[+] Lobster")
            flash_eye_of_pico()
            challenge += 'L'
            time.sleep_ms(300)
        elif not RIGHT.value():
            print("[+] Rum")
            flash_eye_of_pico()
            challenge += 'R'
            time.sleep_ms(300)
        time.sleep_ms(20)

    print(f'challenge={challenge} len={len(challenge)}')
    return challenge

def auth():
    tohash = f'deckhand-{read_challenge()}-{BADGE_ID}'
    #print(f'tohash={tohash}')
    d = hashlib.sha256(tohash.encode()).digest()
    #print(f'hexdigest={d.hex()}')

    # create authorization code
    authcode = []
    for i in range(16):
        p = (i * 7 + 11) & 31
        authcode.append(ALPH[d[p] % len(ALPH)])
    s = ''.join(authcode)
    
    show_auth = f'{s[:4]}-{s[4:8]}-{s[8:12]}-{s[12:]}'
    print(f'Authorization code: {show_auth}')
    badge_display.show("Auth: " + show_auth+ "  ")

    # let's show it another time
    time.sleep_ms(300)
    badge_display.show("Again: " + show_auth+ "  ")


def main():
    print(f"---= Skull Island Badge v{VERSION}=---")
    EYE.value(1)
    badge_display.show_flag()
    print("[+] Flag displayed")

    while True:
        if not UP.value():
            print("[+] UP received -> Skull Seal")
            flash_eye_of_pico()
            badge_display.show("Seal")
            auth()
            time.sleep_ms(300) 
            
        elif not DOWN.value():
            print("[+] DOWN received -> display flag")
            flash_eye_of_pico()
            badge_display.show_flag()
            time.sleep_ms(300) 

        elif not RIGHT.value():
            print("[+] RIGHT received -> display badge id")
            print(f'Badge ID: {BADGE_ID}')
            flash_eye_of_pico()
            badge_display.show(f"Badge ID: {BADGE_ID}  ")
            time.sleep_ms(300)

        elif not LEFT.value():
            print("[+] LEFT received -> Super Shitty Add-On")
            flash_eye_of_pico()
            oled_display()
            badge_display.show("Greetz from Balda and Cryptax")
            time.sleep_ms(300)
            
        time.sleep_ms(20)
    

# ------------- MAIN
main()


