from ht16k33 import HT16K33Matrix
from machine import I2C, Pin

def show(message, log=True):
    # this log message is handy if the LEDs haven't been soldered yet
    if log:
        print(f'Showing {message} - wait till this finishes')
    else:
        print('Showing message - wait till this finishes')
    i2c = I2C(0, scl=Pin(13), sda=Pin(12))
    display = HT16K33Matrix(i2c)
    display.set_brightness(2)
    display.set_angle(90)
    display.scroll_text(message)
    display.clear().draw()
    print('show() finished')

def show_flag():
    # FLAG='ph0wn{Ir0n_MasteR:-)}'
    # OBF = [((ord(c) * 5) + 17) & 0xff for c in FLAG]
    OBF = [65, 25, 1, 100, 55, 120, 126, 75, 1, 55, 236, 146, 246, 80, 85, 10, 171, 51, 242, 222, 130]
    INV = 205   # modular inverse of 5 mod 256

    # this displays FLAG1 of the badge which is shown on the LEDs as soon as they are soldered correctly
    flag = []
    for b in OBF:
        flag.append(chr(((b - 17) * INV) & 0xff))
    show("".join(flag), log=False)

def obfuscate_pearl(s):
    return [((ord(c) * 37) + 91) % 251 for c in s]

'''
def deobfuscate_pearl(obfuscated):
    return ''.join(chr(((x - 91) * 204) % 251) for x in obfuscated

FLAG='ph0wn{++g0ld_shines_in_front_of_u++}'
'''

def black_pearl():
    OBFUSCATED_FLAG = [219, 174, 110, 227, 145, 124, 176, 176, 137, 110, 71, 26, 92, 79, 174, 211, 145, 63, 79, 92, 211, 145, 92, 100, 42, 182, 145, 116, 92, 182, 100, 92, 153, 176, 176, 198]
    print("We intercepted a conversation between Barbossa and Jack Sparrow")
    print("Probably, the Black Pearl Treasure $$$ lies beneath")
    print('- Barbossa: ', obfuscate_pearl("What is the flag?"))
    print('- Jack Sparrow: ', OBFUSCATED_FLAG)
    print('Honestly, the treasure is safe! Not sure you have the guts to steal it.')
    
   


