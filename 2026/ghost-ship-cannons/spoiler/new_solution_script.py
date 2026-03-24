# Requirements:
#   - pymodbus version 3.11.2

from pymodbus.client import ModbusTcpClient

IP, UNIT = "192.168.1.4", 0x1
COILS = [0x0D, 0x35, 0x43, 0x7B, 0xB1]  # CAPTAIN_CONTROL, STOP_LOAD, RAISE_GUN, BALL_READY_CMD, POWDER_READY_CMD  

c = ModbusTcpClient(IP, port=502)
c.connect()

for addr in COILS:
    c.write_coil(addr, True, device_id=UNIT)

c.close()

