# pip install pymodbus

from pymodbus.client import ModbusTcpClient
import time

HOST = "192.168.1.4"  # remplace par l'IP de ton automate
PORT = 502

def regs_to_ascii(registers):
    bytes_list = []
    for r in registers:
        hi = (r >> 8) & 0xFF
        lo = r & 0xFF
        if hi != 0:
            bytes_list.append(hi)
        if lo != 0:
            bytes_list.append(lo)
    try:
        return bytes(bytes_list).decode('ascii', errors='replace')
    except:
        return ""
    
def read_holding_registers(client, start, count):
    """Lit une plage de holding registers et retourne la liste ou None si erreur."""
    rr = client.read_holding_registers(address=start, count=count)
    if rr.isError():
        print(f"Holding registers 0x{start:04x}.. (+{count}) : KO")
    else:
        print(f"Holding registers 0x{start:04x}.. (+{count}) ")
        ascii_repr = regs_to_ascii(rr.registers)
        if ascii_repr:
            print(ascii_repr)
        

client = ModbusTcpClient(HOST, port=PORT)
client.connect()

try:
    read_holding_registers(client, 0, 10)

    client.write_coil(0x000d, True)  # CAPTAIN_TAKES_WHEEL
    time.sleep(0.5)

    client.write_coil(0x007b, True)  # BALL_READY_CMD
    time.sleep(0.5)

    client.write_coil(0xb1, True)  # POWDER_READY_CMD
    time.sleep(0.5)

    client.write_coil(0x0043, True)  # RAISE_GUN
    time.sleep(0.5)

    client.write_coil(0x0035, True)  # STOP_LOAD
    time.sleep(0.5)

    print("should fire")

    read_holding_registers(client, 0, 10)


    

finally:
    client.close()
