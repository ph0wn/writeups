#!/usr/bin/env python

import paho.mqtt.client as mqtt
from time import sleep
from Crypto.Cipher import AES
import base64
import logging

# see: http://www.codekoala.com/posts/aes-encryption-python-using-pycrypto/
BLOCK_SIZE = 16
PADDING = ' '
# one-liner to sufficiently pad the text to be encrypted
pad = lambda s: s + (BLOCK_SIZE - len(s) % BLOCK_SIZE) * PADDING

# one-liners to encrypt/encode and decrypt/decode a string
# encrypt with AES, encode with base64
EncodeAES = lambda c, s: base64.b64encode(c.encrypt(pad(s)))

# team id
teamid = 'teamX'
passwords = ['catwoman@pizza_5', 'France_brie_mars', 'pepper@crocodile', 'tomato@9plane000', 'blue_flower_dres', 'gorgonzola8_xubu', 'four_four_matrix', 'draw_ecology_for', 'sun@aix_30000000', 'ph0wn_tofu_crypt', 'Biot_mercantour_', 'fortiwifi@pool_9', 'France1_weeds000', 'cable@7want00000', 'comte@IoThings_3', 'Cannes@4two00000', 'tofu@your_200000', 'sea@pumpkin_5000', 'python@4framewor', 'sun_fortiswitch_', 'dream2_eat000000', 'aix@swim_2000000', 'donald@fortiwifi', 'plane@fablab_100', 'register@2marcel', 'bond@one_6000000', 'blog@2core000000', 'restaurant@9glas', 'soja@blog_700000', 'olive@glucose_00', 'Antibes@symbian_', 'maze_pluto_Greno', 'movie_laboratory', 'pool@wallet_1000', 'game@5citrus0000', 'throne@dog_40000', 'weeds3_juice0000', 'pollux@jm2l_7000', 'mozart@wine_8000', 'your3_BlackAlps0', 'glucose6_boss000', 'salad@5olive0000', 'python6_flower00', 'dream1_ethernet0', 'movie@your_00000', 'laptop@skin_4000', 'fortianalyzer@ca', 'oboe@7meal000000', 'hack@2manager000', 'ethernet5_park00', 'warming_Cannes_m', 'wizard@wallet_10', 'weeds5_jm2l00000', 'warming_nectaire', 'one_network_blac', 'guitar@picoWell_', 'potter@Savoie_70', 'ios@fish_8000000', 'fortinet@9play00', 'citrus0_laptop00', 'school@5ubuntu00', 'ecology@board_40', 'gorgonzola@potat', 'hoodie_duck_amaz', 'soccer_mercantou', 'Antibes@8android', 'luke@IoThings_10', 'three@5snake0000', 'mint_France_harr', 'play@fortimail_6', 'want_cable_violi', 'glucose_ride_plu', 'cheese3_Grenoble', 'suspect@olive_60', 'oracle@car_00000', 'CTF9_network0000', 'eurecom3_fortisw', 'glucose_cheese_p', 'cat@0Biot0000000', 'pacman0_football', 'android@felicity', 'go@gorgonzola_20', 'hardwear@soja_00', 'glucose@swim_000', 'pumpkin@game_200', 'ios@plane_400000', 'cool1_laboratory', 'skywalker@three_', 'bruce_spectre_se', 'excited@potato_4', 'moon4_disney0000', 'hpux@5draw000000', 'play@meter_10000', 'score_dive_ludov', 'Valbonne1_juice0', 'donald@8redhat00', 'pool9_plane00000', 'catwoman_toothbr', 'goat@2duck000000', 'horse7_jm2l00000']

def select_key():
    global teamid
    logger.debug("select_key(): teamid=%d" % (teamid))
    if teamid < 0 or teamid >= len(passwords):
        logger.debug("invalid teamid=%d" % (teamid))
        return "U&Id1ot!&!@*AEdj"
    logger.debug("Selecting password for teamid=%d: %s" % (teamid, passwords[teamid]))
    return passwords[teamid]

def read_teamid(payload):
    logger.debug("read_teamid(): %s" % (payload))
    len_team = len(payload)
    num = -1
    if len_team > 4:
        try:
            num = int(payload[4:])
            logger.debug("num=%d" % (num))
        except ValueError:
            logger.error("ValueError in read_teamid()")
            pass
    global teamid
    teamid = num
    logger.debug("read_teamid(): Setting teamid: " + teamid)
    return teamid
        

def encrypt_token(secretkey, flag='The flag is ph0wn{ur_lucky_no_diab4te_h4r3}'):
    logger.debug("encryptToken()")
    logger.debug("secretkey=%s len=%d" % (secretkey, len(secretkey)))
    if len(secretkey) != 16:
        return "Bad secret length"
    logger.debug('Encrypting flag with key='+secretkey)
    cipher = AES.new(secretkey, AES.MODE_ECB)
    ciphertext = cipher.encrypt(pad(flag))
    return base64.b64encode("Use team password to decrypt AES: "+ciphertext)


def on_message(client, userdata, msg):
    logger.debug("Topic: "+ msg.topic+ " Message: "+str(msg.payload))

    if msg.topic == "ph0wn18/teamid":
        logger.debug("We got a team id msg")
        read_teamid(str(msg.payload))
    
    if msg.topic == "ph0wn18/glucose-level":
        try:
            level = int(msg.payload)
            if level < 50:
                logger.info("Hypoglycemia risk detected")
                client.publish("ph0wn18/alert", "Hypoglycemia risk!!")
                client.publish("ph0wn18/info", "Ensure your team id is correctly set in ph0wn18/teamid (e.g. 'team4') - or set it (admin required)")
                logger.debug("Before flag")
                thekey = select_key()
                client.publish("ph0wn18/flag", "Encrypted token (team key): "+encrypt_token(thekey) )
                client.publish("ph0wn18/info", "AES 128 ECB")
            if level > 200:
                logger.info("High blood sugar detected")
                client.publish("ph0wn18/alert", "Hyperglycemia risk!!")
                client.publish("ph0wn18/flag", "Nice try. Try and generate another type of alert.")
        except ValueError:
            logger.warn("ValueError exception: publishing to ph0wn18/alert")
            client.publish('ph0wn18/alert', "Hmm. Someone's trying to hack me? You're getting close.")

# MAIN ------------------------------------------
logging.basicConfig(filename='/dev/stdout', format='[%(name)s] %(asctime)s %(message)s', datefmt='%m/%d/%Y %I:%M:%S', level=logging.DEBUG)
logger = logging.getLogger('superadmin')
logger.debug('Logger configured')
            
client = mqtt.Client('superadmin') # client id should be unique
client.username_pw_set(username='superadmin', password='Th1sOneMustBeVerySecuRe@')
client.on_message = on_message

client.connect("127.0.0.1", 1883, 60)
logger.info("Connected")
client.subscribe("ph0wn18/#")
logger.info("Subscribed to all topics")
logger.info("Ready")
client.loop_forever() # this is needed to keep on processing incoming messages
