from pymodbus.client import ModbusTcpClient
import argparse

UNIT = 0x1

# Same coils used in the solution
COILS = [
    0x0D,  # CAPTAIN_CONTROL
    0x35,  # STOP_LOAD
    0x43,  # RAISE_GUN
    0x7B,  # BALL_READY_CMD
    0xB1,  # POWDER_READY_CMD
]

def parse_args():
    parser = argparse.ArgumentParser(description="Reset challenge coils on target PLC.")
    parser.add_argument("host", nargs="?", default="chal.ph0wn.org")
    parser.add_argument("port", nargs="?", type=int, default=5020)
    return parser.parse_args()


def main():
    args = parse_args()
    c = ModbusTcpClient(args.host, port=args.port, timeout=3)
    assert c.connect(), "TCP connect failed"

    for addr in COILS:
        r = c.write_coil(addr, False, device_id=UNIT)
        if r.isError():
            raise RuntimeError(f"Reset failed at coil {hex(addr)}")

    c.close()
    print(f"[+] Fake PLC reset on {args.host}:{args.port}: all challenge coils set to 0.")

if __name__ == "__main__":
    main()
