from pymodbus.client import ModbusTcpClient
import argparse

ADDR = 0
COUNT = 10

def parse_args():
    parser = argparse.ArgumentParser(description="Read holding registers from target PLC.")
    parser.add_argument("host", nargs="?", default="chal.ph0wn.org")
    parser.add_argument("port", nargs="?", type=int, default=5020)
    return parser.parse_args()


def main():
    args = parse_args()
    c = ModbusTcpClient(host=args.host, port=args.port, timeout=3)
    assert c.connect(), "TCP connect failed"

    resp = c.read_holding_registers(address=ADDR, count=COUNT)
    if resp.isError():
        raise RuntimeError(f"Modbus error: {resp}")

    regs = resp.registers
    b = bytearray()
    for r in regs:
        b.extend(r.to_bytes(2, "big"))

    print("Raw registers:", regs[:16], "..." if len(regs) > 16 else "")
    print("ASCII:", b.replace(b"\x00", b"").decode(errors="ignore"))

    c.close()


if __name__ == "__main__":
    main()
