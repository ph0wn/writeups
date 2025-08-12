#!/usr/bin/expect -f
# sudo apt-get install expect
# see https://pantz.org/software/expect/expect_examples_and_tips.html

#log_user 0

send_user "========= Ph0wn Lungo =========\n"
send_user "              by @cryptax\n"
send_user "\nMake sure lid has been opened to insert coffee and closed\n\n"

set prompt ">"
set address "D2:A7:4C:76:F3:E0"
set timeout 2

# Launching gatttool
send_user "Launching gatttool..."
spawn gatttool -b $address -I -t random --sec-level=high
expect {
    timeout { send_user "Failed to launch gatttool\n"; exit 1 }
    $prompt
}
send_user "done\n"

# Connect
send_user "Connecting..."
set timeout 30
send "connect\r"
expect {
    timeout { send_user "Failed to connect. If this persists, try to power off/on adapter and scan.\n"; exit 1}
    "Error" { send_user "Connection failed. Welcome to BLE! Try again\n"; exit 1 }
    "Connection successful"
}
send_user "done\n"

# Authorization
set timeout 2
send_user "Sending authorization code..."
send "char-write-req 0x0014 86A8125F064EF42F\r"
expect {
    timeout { send_user "Failed to write authorization\n"; exit 1 }
    "Characteristic value was written successfully"
}
send_user "done\n"

# Brew !
set timeout 3
send_user "Brew coffee!...\n"
send "char-write-req 0x0024 03050704000000000002\r"
expect {
    timeout { send_user "Failed to brew lungo\n"; exit 1 }
    "Characteristic value was written successfully"
}
send_user "Enjoy your coffee!\n"

send "disconnect"
send_user "Disconnected"

