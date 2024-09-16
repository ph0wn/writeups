import discord
from discord.ext import commands
from discord.ext.commands import Bot
import os
import re 
import random
import logging
from dotenv import load_dotenv

# ------------- DISCORD SETUP
load_dotenv()
VERSION = '0.14'
TOKEN = os.getenv('DISCORD_TOKEN')
intents = discord.Intents.default()
intents.message_content = True
MAX_ENTRIES = 1000
user_message_tracker = set()   # to keep track of authors the bot talked to
bot = Bot(command_prefix='/', intents=intents)


# ------------- LOGGING SETUP
logging.basicConfig(level=logging.DEBUG, format='%(asctime)s- %(levelname)s -%(message)s')


# ------------- TUBBLE SETUP

tubble_quotes = ["I observed the sky yesterday night, and spotted something strange",
                 "Not sure if what I saw yesterday was a new constellation of satellites, or aliens",
                 "If what I saw yesterday was aliens, I'm in for the Nobel Prize",
                 "Hey? Have you heard the latest news? Pico le Croco launched a new constellation of satellites, called PicoStar",
                 "My telescope is perfect for professional observations + I patched its firmware.",
                 "I have a bunch of telescope/GoTo mount firmware if ever you need",
                 "Pfff, the James Webb Space Telescope is just a waste of money. Hubble is far better",
                 "Hubble telescope is not expected to re-enter Earth's atmosphere before mid 2030",
                 "Chrisrdlg is very smart. Congratulations. But I'm smarter as an astronomer...😘",
                 "My telescope is bigger than the one Azox has. Just saying 😇"
                ]
                        
BINARY_FILE_PATH = './mc021_motor_controller_firmware_0356.zip'
IMAGE_FILE_PATH = './telescope.jpg'
MP3_FILE_PATH = './space-invaders.mp3'

@bot.event
async def on_ready():
    logging.debug(f'Logged on as {bot.user.name} - {bot.user.id}')

@bot.command()
async def whoami(ctx):
    """Who I am"""
    await ctx.send("I am Erwin Tubble, a rising star of astronomy :) I am interested in astronomy challenge teasers")

@bot.command()
async def firmware(ctx):
    """Explains how to request firmware from the bot"""
    await ctx.send("If you wish a firmware, please send me a DM. The syntax must me: SEND FIRMWARE xxxxxxx, precisely describing what firmware you are looking for.")

@bot.command()    
async def version(ctx):
    """Returns the version of this bot"""
    await ctx.send(VERSION)

async def send_binary_file(user):
    try:
        # Create a discord.File object
        file = discord.File(BINARY_FILE_PATH,
                            filename=os.path.basename(BINARY_FILE_PATH))
      
        # Send the file with a message
        await user.send("This is the firmware for my telescope's GOTO mount:",
                        file=file)
        logging.debug(f"Sent firmware binary file to {user.name}")

    except Exception as e:
        logging.error(f"Failed to send file: {e}")

def say_hello(username, userid):
    if userid not in user_message_tracker:
        if len(user_message_tracker) >= MAX_ENTRIES:
            user_message_tracker.clear()  # Clear the tracker if it exceeds the maximum allowed entries
        user_message_tracker.add(userid)
        logging.debug(f'Saying hello to {username}')
        return f'Nice to meet you {username}! I am Erwin Tubble, a rising star of astronomy :) If you need a firmware, please specify very precisely the brand and full model name.'
    return None

async def dm_firmware(message, author):
    regex = re.compile('[^a-z]')
    filtered = regex.sub('', message)

    if 'sendfirmware' in filtered:
        #logging.debug('[+] Check 1: got a SEND FIRMWARE message')
        if 'staradventurer' in filtered or 'skywatcher' in filtered:
            #logging.debug('[+] Check 2: OK Star Adventurer/Sky Watcher')
            if 'gti' in message:
                await send_binary_file(author)
                return True
            else:
                logging.debug(f'[-] Check 3 KO: GTi: {filtered}.')
                await author.send("Hmm. I might have that. Which model precisely, please?")
                return True
        else:
            logging.debug(f'[-] Check 2 KO: {filtered}')
            await author.send("I'm afraid I don't have this firmware...")
            return True
    logging.debug(f'[-] Check 1 KO: not a send firmware message: {filtered}')
    return False

async def generic_answer(message):
    # if we haven't answered yet, select a random message
    # including possibly an image of the used telescope
    # I'm increasing chances to get the image, because it's necessary to solve
    n = random.randint(0, len(tubble_quotes)+5)
    if n < len(tubble_quotes):
        await message.channel.send(tubble_quotes[n])
    if n >= len(tubble_quotes) and n < len(tubble_quotes) +5:
        image_file = discord.File(IMAGE_FILE_PATH,
                                  filename=os.path.basename(IMAGE_FILE_PATH))
        logging.debug(f'Sending {IMAGE_FILE_PATH} to channel')
        await message.channel.send("Here is a picture of my telescope: ", file=image_file)
    if n == len(tubble_quotes) + 5:
        mp3_file = discord.File(MP3_FILE_PATH,
                                  filename=os.path.basename(MP3_FILE_PATH))
        await message.channel.send('', file=mp3_file)
        logging.debug(f'Sending {MP3_FILE_PATH} to channel')


@bot.event
async def on_message(message):
    await bot.process_commands(message)

    if message.author == bot.user:
        # don't answer to my own messages
        return
    
    # Check if the message is a reply
    if message.reference is not None:
        # Get the original message
        original_message = await message.channel.fetch_message(message.reference.message_id)
        # Check if the original message was sent by the bot
        if original_message.author != bot.user:
            logging.debug(f'This is a reply to {original_message.author} - not concerned, not replying')
            return

    if isinstance(message.channel, discord.DMChannel):
        logging.info(f'Direct Message from {message.author}: {message.content}')
        msg = say_hello(message.author, message.author.id)
        if msg is not None:
            await message.channel.send(msg)
        if await dm_firmware(message.content.lower(), message.author):
            # if the message was handled by DM, no need to answer more
            return
    else:
        # message only for the channel
        if 'hi ' in message.content.lower() or 'hi' == message.content.lower():
            msg = say_hello(message.author, message.author.id)
            if msg is not None:
                await message.channel.send(msg)
            return
    if message.content.lower().startswith('/'):
        logging.debug('Command should have been processed already')
        return

    if any(x in message.content.lower() for x in ['download', 'firmware', 'file', 'goto', 'mount', 'equatorial']):
        await message.channel.send("Please DM me for firmware downloads. Specify brand and model as precisely as possible. And begin your message with SEND FIRMWARE: xxxx")
        return

    # potential answers both in DM or channel
    # special cases
    if any(x in message.content.lower() for x in ['star', 'nebula', 'sun', 'fire']):
        await message.add_reaction('\N{DARK SUNGLASSES}')
        return

    if any(x in message.content.lower() for x in ['space', 'm42', 'messier', 'venus', 'mars', 'jupiter', 'rocket', 'alien', 'race']):
        await message.add_reaction('\N{ROCKET}')
        return

    if any(x in message.content.lower() for x in ['picolecroco', 'ph0wn', 'insomnihack', 'sthack', 'barbhack', 'secsea', 'grehack', 'penthertz', 'serma', 'passthesalt', 'blackalps']):
        await message.add_reaction('\N{HEAVY BLACK HEART}')
        return

    if any(x in message.content.lower() for x in ['suisse', 'beurre', 'soudure', 'swiss', 'cheese', 'mountains', 'geneva', 'lausanne']):
        await message.add_reaction('🇨🇭')

    if any(x in message.content.lower() for x in ['cong', 'colomars', 'pastis', 'caing', 'provence', 'calisson']):
        await message.channel.send(f'I speak only very little Marseillais-euh')
        return
    
    if 'python2' in message.content.lower():
        await message.channel.send("Oh, chrisrdlg, is that you?")
        return 
    
    if 'xor' in message.content.lower():
        await message.channel.send("Maybe aliens encrypt their messages with XOR, and that's why Azox was so quick to solve? I don't use XOR though, and my telescope doesn't either.")
        return 
    
    if 'flag' in message.content.lower():
        await message.channel.send("There used to be a flag on the moon, but I'm not sure that's what you mean.")
        return

       

    # generic case
    await generic_answer(message)
       
bot.run(TOKEN)
