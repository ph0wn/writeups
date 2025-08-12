# FROZEN MODULES

Some of these modules are needed by the firmware to work correctly. We are gonna describe only the ones we created.

- bias.py: main module used for the challenge **Melting Point**
- credentials.py: contains the method to get SSID and password of the AP
- http_requests.py: module to perform na HTTP request
- ob.py: module used to obfuscate the strings. It contains the methods to both encrypt and decrypt the strings
- oled.py: module used as interface with the LCD
- server.py: it justs retrieves the server's ip or name
- thesignal.py: main module used for the challenge **The Signal**
- wifi.py: module used to easily connect and disconnect with the wifi
