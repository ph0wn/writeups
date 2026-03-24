#!/usr/bin/env python3
import re
import time
import argparse
import serial

SHIP_LENS = [3, 3, 2, 2, 1]
N = 24  # 0..23

def xorshift32(s: int) -> int:
    s &= 0xFFFFFFFF
    s ^= (s << 13) & 0xFFFFFFFF
    s ^= (s >> 17) & 0xFFFFFFFF
    s ^= (s << 5)  & 0xFFFFFFFF
    return s & 0xFFFFFFFF

def rand_mod(state: int, mod: int):
    state = xorshift32(state)
    return state, state % mod

def place_ships(seed: int):
    state = seed & 0xFFFFFFFF
    occ = [[0]*N for _ in range(N)]
    cells = []

    def collide(x, y, ln, vert):
        for i in range(ln):
            xx = x if vert else x + i
            yy = y + i if vert else y
            if occ[yy][xx]:
                return True
        return False

    def mark(x, y, ln, vert):
        for i in range(ln):
            xx = x if vert else x + i
            yy = y + i if vert else y
            occ[yy][xx] = 1
            cells.append((yy, xx))  # stored as (row, col)

    for ln in SHIP_LENS:
        while True:
            state, vert = rand_mod(state, 2)  # 0=horiz, 1=vert
            state, x    = rand_mod(state, N)
            state, y    = rand_mod(state, N)

            if vert and y + ln > N: y = N - ln
            if not vert and x + ln > N: x = N - ln

            if not collide(x, y, ln, vert):
                mark(x, y, ln, vert)
                break

    # enforce uniqueness (prevents any accidental double-fire)
    uniq, seen = [], set()
    for rc in cells:
        if rc not in seen:
            seen.add(rc)
            uniq.append(rc)
    return uniq

def _readline(ser, timeout=0.6) -> str:
    """Read a line (up to \\n). Returns '' on timeout."""
    t0 = time.time()
    buf = bytearray()
    while time.time() - t0 < timeout:
        b = ser.read(1)
        if not b:
            continue
        if b == b"\n":
            break
        buf += b
    return buf.decode(errors="ignore").strip("\r").strip()

def send_cmd_1line(ser, cmd: str, timeout=1.0) -> str:
    """
    Send cmd; device answers in exactly 1 line.
    Filters out: echoed command + prompt lines like '$'.
    """
    ser.reset_input_buffer()
    ser.write((cmd + "\n").encode())

    t0 = time.time()
    while time.time() - t0 < timeout:
        line = _readline(ser, timeout=timeout)
        if not line:
            continue
        if line == cmd:
            continue  # echo
        if line in ("$", ">"):
            continue  # bare prompt
        if line.startswith("$"):
            line = line.lstrip("$").strip()  # prompt prefix
            if not line:
                continue
        return line
    return ""

def get_seed(ser) -> int:
    print("[*] Reading seed from game_id")
    print("[>] game_id")
    out = send_cmd_1line(ser, "game_id", timeout=1.5)
    m = re.search(r"0x([0-9a-fA-F]{8})", out)
    if not m:
        print(f"[-] Seed not found in: {out!r}")
        raise RuntimeError("Seed not found")
    seed = int(m.group(1), 16)
    print(f"[+] seed = 0x{seed:08x}")
    return seed

def main():
    ap = argparse.ArgumentParser(description="Battleship CTF solver")
    ap.add_argument("--port", default="/dev/ttyACM0")
    ap.add_argument("--baud", type=int, default=115200)
    ap.add_argument("--delay", type=float, default=0.02)
    args = ap.parse_args()

    print(f"[*] opening {args.port} @ {args.baud}")
    with serial.Serial(args.port, args.baud, timeout=0.1) as ser:
        seed = get_seed(ser)
        targets = place_ships(seed)
        print(f"[+] computed {len(targets)} unique ship cells")

        fired = set()
        for i, (row, col) in enumerate(targets, 1):
            if (row, col) in fired:
                continue
            fired.add((row, col))

            cmd = f"fire {row} {col}"   # REVERTED: keep row,col as before
            print(f"[*] ({i:02d}/{len(targets):02d}) {cmd}")
            print(f"[>] {cmd}")
            ans = send_cmd_1line(ser, cmd, timeout=1.0)
            if ans:
                print(ans)
            else:
                print("[-] no response (timeout)")
            time.sleep(args.delay)

        print("[*] requesting flag")
        print("[>] flag 1")
        flag_line = send_cmd_1line(ser, "flag 1", timeout=5.0)
        if flag_line:
            print(f"[+] {flag_line}")
        else:
            print("[-] no response to 'flag 1'")

if __name__ == "__main__":
    main()
