from pymodbus.client import ModbusTcpClient
import pymodbus

HOST = "192.168.1.4"   # PLC IP
PORT = 502
ADDR = 0               # %MW0
COUNT = 10              # %MW0..%MW9


c = ModbusTcpClient(host=HOST, port=PORT, timeout=3)
assert c.connect(), "TCP connect failed"

resp = c.read_holding_registers(address=ADDR, count=COUNT)
if resp.isError():
    raise RuntimeError(f"Modbus error: {resp}")

regs = resp.registers
b = bytearray()
for r in regs:
    b.extend(r.to_bytes(2, "big"))  # Modbus is big-endian per register

print("Raw registers:", regs)
print("ASCII:", b.replace(b"\x00", b"").decode(errors="ignore"))
c.close()
