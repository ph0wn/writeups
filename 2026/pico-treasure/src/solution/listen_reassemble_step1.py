#!/usr/bin/env python3
"""
Listener / reassembler for the PicoTreasure UDP broadcaster.

Usage:
  python3 listen_reassemble_step1.py --port 5005 --out treasure.bin --timeout 300
"""

import argparse
import base64
import socket
import select
import time
import os
from typing import Dict, Tuple

MAGIC = b"PicoTreasure"
ENC = "utf-8"


def parse_packet(raw: bytes) -> Tuple[str, str, str, bytes]:
    """
    Parse a raw UDP packet. Returns (session, tag, meta, payload_bytes)
    - For chunk packets: tag is "chunk", meta is "i/total", payload is the decoded bytes
    - For announce: tag is "announce", meta contains text, payload is b""
    - For END: tag is "end", meta contains text, payload is b""
    Raises ValueError on unexpected format.
    """
    try:
        parts = raw.split(b"|", 3)
        if len(parts) < 3:
            raise ValueError("Too few parts")
        if parts[0] != MAGIC:
            raise ValueError("Bad magic")
        session = parts[1].decode(ENC)
        tagpart = parts[2].decode(ENC)

        if tagpart == "ANNOUNCE":
            meta = parts[3].decode(ENC) if len(parts) > 3 else ""
            return session, "announce", meta, b""
        if tagpart == "END":
            meta = parts[3].decode(ENC) if len(parts) > 3 else ""
            return session, "end", meta, b""

        # Otherwise expect idx/total and base64 payload
        if len(parts) < 4:
            raise ValueError("Malformed chunk (missing payload)")
        idx_total = tagpart  # like "12/678"
        b64payload = parts[3].strip()
        try:
            payload = base64.b64decode(b64payload, validate=True)
        except Exception as e:
            raise ValueError(f"Base64 decode failed: {e}")
        return session, "chunk", idx_total, payload

    except Exception as e:
        raise ValueError(f"parse error: {e}")


def assemble_and_write(chunks: Dict[int, bytes], total: int, outpath: str) -> None:
    """
    Assemble chunks (1-indexed) and write to outpath atomically.
    """
    # sanity: check we have all
    missing = [i for i in range(1, total + 1) if i not in chunks]
    if missing:
        raise RuntimeError(f"Missing chunks: {missing[:10]}... (total {len(missing)})")

    # write to temp then rename
    tmp = outpath + ".part"
    with open(tmp, "wb") as f:
        for i in range(1, total + 1):
            f.write(chunks[i])
    os.replace(tmp, outpath)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--port", type=int, default=5005, help="UDP port to listen on (default: 5005)")
    ap.add_argument("--out", default="treasure.bin", help="Output filename (default: treasure.bin)")
    ap.add_argument("--timeout", type=int, default=300, help="Seconds of inactivity to wait before exiting (default: 300)")
    ap.add_argument("--bind", default="0.0.0.0", help="Address to bind (default: 0.0.0.0)")
    args = ap.parse_args()

    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    # allow reuse and receive broadcasts
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    try:
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEPORT, 1)
    except Exception:
        pass
    sock.bind((args.bind, args.port))
    sock.setblocking(False)

    print(f"[+] Listening on {args.bind}:{args.port} (timeout {args.timeout}s) ...")
    sessions = {}  # session -> { 'total': int, 'chunks': {idx: bytes}, 'last_progress_ts': float }
    last_activity = time.time()

    try:
        while True:
            # wait for data with a small timeout so we can check for inactivity
            rlist, _, _ = select.select([sock], [], [], 1.0)
            if not rlist:
                # check inactivity
                if time.time() - last_activity > args.timeout:
                    print("[!] Timeout reached with no progress. Exiting.")
                    break
                continue

            raw, addr = sock.recvfrom(65536)
            last_activity = time.time()
            try:
                session, tag, meta, payload = parse_packet(raw)
            except ValueError as e:
                # ignore unknown packets
                print(f"[x] Ignoring packet from {addr}: {e}")
                continue

            if session not in sessions:
                sessions[session] = {"total": None, "chunks": {}, "last_progress_ts": time.time()}
                print(f"[+] New session: {session}")

            sess = sessions[session]
            sess["last_progress_ts"] = time.time()

            if tag == "announce":
                print(f"[i] ANNOUNCE ({session}): {meta}")
                # optionally parse meta to prefill total if provided (not required)
                # e.g. meta contains "chunks=678"
                try:
                    parts = meta.split("|")
                    for p in parts:
                        if p.startswith("chunks="):
                            sess["total"] = int(p.split("=")[1])
                            print(f"[i] total chunks hinted: {sess['total']}")
                except Exception:
                    pass

            elif tag == "end":
                print(f"[i] END ({session}): {meta}")
                # do nothing special; we rely on chunk counting

            elif tag == "chunk":
                # meta like "12/678"
                try:
                    idx_str, total_str = meta.split("/")
                    idx = int(idx_str)
                    total = int(total_str)
                except Exception:
                    print(f"[x] Bad idx/total: {meta}")
                    continue

                if sess["total"] is None:
                    sess["total"] = total
                    print(f"[+] session {session} total set to {total}")

                # dedupe
                if idx in sess["chunks"]:
                    # duplicate
                    # print(f"[.] dup {idx}/{total} from {addr}")
                    continue

                sess["chunks"][idx] = payload
                print(f"[>] got {idx}/{total} (session {session})  stored {len(sess['chunks'])}/{sess['total']}")
                # check completion
                if sess["total"] is not None and len(sess["chunks"]) >= sess["total"]:
                    outname = args.out
                    # if multiple sessions possible, include session id in output to avoid overwriting
                    if len(sessions) > 1:
                        base, ext = os.path.splitext(outname)
                        outname = f"{base}.{session}{ext}"
                    print(f"[+] All chunks received for session {session}. Assembling to {outname} ...")
                    try:
                        assemble_and_write(sess["chunks"], sess["total"], outname)
                        print(f"[+] Wrote {outname} ({sess['total']} chunks). Exiting.")
                        return
                    except Exception as e:
                        print(f"[!] Error assembling: {e}")
                        # keep listening in case retransmissions arrive

    except KeyboardInterrupt:
        print("\n[!] Interrupted by user. Exiting.")

    # on exit, optionally write partial data for debugging
    for session, sess in sessions.items():
        if sess["chunks"]:
            partname = f"{args.out}.{session}.partial"
            try:
                print(f"[i] Writing partial capture for session {session} to {partname}")
                assemble_and_write(sess["chunks"], max(sess["chunks"].keys()), partname)
            except Exception:
                # best-effort: write concatenation of received chunks in index order
                with open(partname, "wb") as f:
                    for i in sorted(sess["chunks"].keys()):
                        f.write(sess["chunks"][i])
                print(f"[i] Wrote best-effort partial to {partname}")

    print("[*] Done.")


if __name__ == "__main__":
    main()
