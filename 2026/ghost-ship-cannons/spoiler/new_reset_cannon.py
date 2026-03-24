# Requirements:
#   - pymodbus version 3.11.2

from pymodbus.client import ModbusTcpClient
import time

IP = "192.168.1.4"
UNIT = 0x1  

COIL_READY = 0x11
COILS_ZERO = [0x0B, 0x0D, 0x13, 0x17, 0x1D, 0x1F, 0x25,
              0x29, 0x2B, 0x2F, 0x35, 0x3B, 0x3D, 0x43,
              0x47, 0x53, 0x7B, 0xB1]

MW_DEFAULTS = {
    0x0: 0x5452, 0x1: 0x4541, 0x2: 0x5355, 0x3: 0x5245,
    0x4: 0x5F4C, 0x5: 0x4F43, 0x6: 0x4B45, 0x7: 0x4421,
    0x8: 0x0000, 0x9: 0x0000
}

c = ModbusTcpClient(IP, port=502)

for addr, val in MW_DEFAULTS.items():
    c.write_register(addr, val, device_id=UNIT)

for m in COILS_ZERO:
    c.write_coil(m, False, device_id=UNIT)

c.write_coil(COIL_READY, True, device_id=UNIT)

ok = c.read_coils(COIL_READY, count=1, device_id=UNIT).bits[0]
w0 = c.read_holding_registers(0x0, count=0xA, device_id=UNIT).registers
c.close()

print("Reset done. READY=1; MW0..MW9 restored:", [hex(x) for x in w0])

