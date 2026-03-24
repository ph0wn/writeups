#!/usr/bin/env python3
"""
Pico treasure broadcaster over Wi-Fi using UDP broadcast.

- Sender reads a binary file, splits into chunks, base64-encodes each chunk,
  and broadcasts packets like:
    PT|<session>|<idx>/<total>|<b64>

Run:
  python3 broadcast_bin.py treasure.bin

Optional:
  python3 broadcast_bin.py ../time_lock_ctf/target/release/time_lock_ctf --bcast 255.255.255.255 --port 5005 --loop --delay 0.03
"""

import argparse
import base64
import math
import os
import random
import socket
import time


MAGIC = "PicoTreasure"


def make_session_id() -> str:
    return f"{random.getrandbits(32):08x}"


def broadcast_file(
    filename: str,
    bcast_ip: str,
    port: int,
    chunk_size: int,
    delay: float,
    loop: bool,
    shuffle: bool,
    announce_every: int,
) -> None:
    data = open(filename, "rb").read()
    total = math.ceil(len(data) / chunk_size)
    session = make_session_id()

    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)

    def send(pkt: bytes) -> None:
        sock.sendto(pkt, (bcast_ip, port))

    print(f"[+] Broadcasting: {filename} ({len(data)} bytes)")
    print(f"[+] UDP broadcast: {bcast_ip}:{port}")
    print(f"[+] Session: {session} | chunks: {total} | chunk_size: {chunk_size} | delay: {delay}s")
    if loop:
        print("[+] Looping enabled (continuous broadcast)")
    if shuffle:
        print("[+] Shuffle enabled (chunks sent in random order each cycle)")

    cycle = 0
    while True:
        cycle += 1
        indices = list(range(total))
        if shuffle:
            random.shuffle(indices)

        # Announce
        announce = f"{MAGIC}|{session}|ANNOUNCE|file={os.path.basename(filename)}|bytes={len(data)}|chunks={total}|chunk={chunk_size}|cycle={cycle}"
        send(announce.encode())

        for n, i in enumerate(indices, start=1):
            chunk = data[i * chunk_size: (i + 1) * chunk_size]
            b64 = base64.b64encode(chunk)
            # Packet format: PT|session|idx/total|<base64>
            pkt = b"|".join(
                [
                    MAGIC.encode(),
                    session.encode(),
                    f"{i+1}/{total}".encode(),
                    b64,
                ]
            )
            send(pkt)

            if announce_every > 0 and (n % announce_every == 0):
                send(announce.encode())
                print(f"[>] Cycle {cycle} sent {n}/{total} chunks (re-announced metadata).")

            if delay > 0:
                time.sleep(delay)

        end = f"{MAGIC}|{session}|END|cycle={cycle}"
        send(end.encode())
        print(f"[>] Cycle {cycle} sent ({total} chunks).")

        if not loop:
            break

        # Sleep
        time.sleep(5.0)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("file", help="Binary file to broadcast (e.g. treasure.bin)")
    ap.add_argument("--bcast", default="255.255.255.255", help="Broadcast IP (default: 255.255.255.255)")
    ap.add_argument("--port", type=int, default=5005, help="UDP port (default: 5005)")
    ap.add_argument("--chunk", type=int, default=512, help="Raw bytes per chunk (default: 512)")
    ap.add_argument("--delay", type=float, default=0.03, help="Delay between chunks in seconds (default: 0.03)")
    ap.add_argument("--loop", action="store_true", help="Continuously repeat the broadcast")
    ap.add_argument("--shuffle", action="store_true", help="Send chunks in random order each cycle")
    ap.add_argument("--announce-every", type=int, default=50, help="Re-announce metadata every N chunks (default: 50)")
    args = ap.parse_args()

    broadcast_file(
        filename=args.file,
        bcast_ip=args.bcast,
        port=args.port,
        chunk_size=args.chunk,
        delay=args.delay,
        loop=args.loop,
        shuffle=args.shuffle,
        announce_every=args.announce_every,
    )


if __name__ == "__main__":
    main()
