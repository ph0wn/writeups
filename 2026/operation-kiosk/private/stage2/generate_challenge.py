"""
Challenge generation script for Operation-Kiosk — Stage 2.

Produces the two files that are placed on Croco's kiosk desktop:
  navtex_signal.wav          — NAVTEX SITOR-B audio (from navtex_generator)
  encrypted_arome.grib2.enc  — ECIES-encrypted GRIB2 with the flag appended

Also writes ec_settings.json to the private folder for challenge authors.

Message source:
  - If a message template file exists (default: "message" in script dir), it is
    read and {private_key_hex} and {public_key_hex} are replaced. Line endings
    are normalized to CRLF for NAVTEX.
  - Otherwise the built-in NAVTEX_TEMPLATE is used (only {private_key_hex}).

Usage:
    python generate_challenge.py [--output-dir DIR] [--message-file PATH] [--preamble SEC]
"""

import argparse
import os
import sys
from typing import Optional

from grib_encryptor import (
    generate_p256_keypair,
    private_key_to_hex,
    public_key_to_hex,
    ecies_encrypt,
    save_ec_settings,
)
from navtex_generator import generate_navtex_wav

# ---------------------------------------------------------------------------
# Configuration — edit these as needed
# ---------------------------------------------------------------------------

FLAG = b"ph0wn{this_M@rine_Grib_stuff_is_N0_JOKE?}"

NAVTEX_TEMPLATE = (
    "ZCZC KA37\r\n"
    "NAVAREA II SECURITY WARNING 037/26\r\n"
    "\r\n"
    "PIRATE ACTIVITY REPORTED.\r\n"
    "VESSELS ARE ADVISED TO EXERCISE EXTREME CAUTION\r\n"
    "IN THE VICINITY OF REPORTED INCIDENTS.\r\n"
    "\r\n"
    "YOU HAVE GOT EVERYTHING YOU NEED\r\n"
    "IN YOUR GRIB FILE GOOD LUCK.\r\n"
    "\r\n"
    "DECRYPT KEY P256:\r\n"
    "{private_key_hex}\r\n"
    "NNNN"
)

# Source GRIB2 file (original, unmodified meteorological data)
GRIB_SOURCE = "mod_arome.grib2"

# Output filenames (given to players)
OUT_WAV = "navtex_signal.wav"
OUT_ENC = "encrypted_arome.grib2.enc"

# Private settings file (not given to players)
OUT_SETTINGS = "ec_settings.json"

# Message template file (same dir); placeholders: {private_key_hex}, {public_key_hex}. Use "" to disable.
MESSAGE_TEMPLATE_FILE = "message"


# ---------------------------------------------------------------------------
# Generation steps
# ---------------------------------------------------------------------------

def _step_generate_keys():
    print("[1/4] Generating P-256 key pair ...")
    private_key, public_key = generate_p256_keypair()
    return private_key, public_key


def _step_save_settings(private_key, public_key, output_dir: str):
    path = os.path.join(output_dir, OUT_SETTINGS)
    print(f"[2/4] Saving EC settings → {path}")
    save_ec_settings(private_key, public_key, path)
    return path


def _step_generate_navtex(private_key, public_key, output_dir: str, message_file: Optional[str], preamble_seconds: float):
    hex_key = private_key_to_hex(private_key)
    pub_hex = public_key_to_hex(public_key)
    script_dir = os.path.dirname(os.path.abspath(__file__))
    template_path = os.path.join(script_dir, message_file) if message_file else None
    if template_path and os.path.isfile(template_path):
        with open(template_path, "r", encoding="utf-8") as f:
            message = f.read()
        message = message.replace("{private_key_hex}", hex_key).replace("{public_key_hex}", pub_hex)
        if "\n" in message and "\r\n" not in message:
            message = message.replace("\n", "\r\n")
    else:
        message = NAVTEX_TEMPLATE.format(private_key_hex=hex_key)
    out_path = os.path.join(output_dir, OUT_WAV)
    print(f"[3/4] Generating NAVTEX audio → {out_path}")
    generate_navtex_wav(message, out_path, preamble_seconds=preamble_seconds)
    return out_path


def _step_encrypt_grib(public_key, script_dir: str, output_dir: str):
    grib_path = os.path.join(script_dir, GRIB_SOURCE)
    if not os.path.isfile(grib_path):
        print(f"ERROR: GRIB source file not found: {grib_path}", file=sys.stderr)
        sys.exit(1)

    print(f"[4/4] Reading GRIB2 ({grib_path}), appending flag, encrypting ...")

    with open(grib_path, "rb") as fh:
        grib_bytes = fh.read()

    # Append the flag directly after the GRIB data (after the last "7777" marker)
    payload = grib_bytes + FLAG

    ciphertext = ecies_encrypt(public_key, payload)

    out_path = os.path.join(output_dir, OUT_ENC)
    with open(out_path, "wb") as fh:
        fh.write(ciphertext)

    print(f"    Encrypted size: {len(ciphertext):,} bytes → {out_path}")
    return out_path


# ---------------------------------------------------------------------------
# Entry point
# ---------------------------------------------------------------------------

def main():
    parser = argparse.ArgumentParser(description="Generate Operation-Kiosk Stage 2 challenge files")
    parser.add_argument(
        "--output-dir",
        default=None,
        help="Directory for generated files (default: script directory)",
    )
    parser.add_argument(
        "--message-file",
        default=MESSAGE_TEMPLATE_FILE,
        metavar="PATH",
        help="Message template file with {private_key_hex} and {public_key_hex}; use '' to disable",
    )
    parser.add_argument(
        "--preamble",
        type=float,
        default=10.0,
        metavar="SEC",
        help="NAVTEX phasing preamble duration in seconds (default: 10)",
    )
    args = parser.parse_args()

    script_dir = os.path.dirname(os.path.abspath(__file__))
    output_dir = args.output_dir or script_dir
    message_file = (args.message_file or MESSAGE_TEMPLATE_FILE).strip() or MESSAGE_TEMPLATE_FILE

    os.makedirs(output_dir, exist_ok=True)

    private_key, public_key = _step_generate_keys()
    _step_save_settings(private_key, public_key, output_dir)
    _step_generate_navtex(private_key, public_key, output_dir, message_file, args.preamble)
    _step_encrypt_grib(public_key, script_dir, output_dir)

    print("\nDone. Files for Croco's kiosk desktop:")
    print(f"  {os.path.join(output_dir, OUT_WAV)}")
    print(f"  {os.path.join(output_dir, OUT_ENC)}")
    print(f"\nPrivate reference (do NOT give to players):")
    print(f"  {os.path.join(output_dir, OUT_SETTINGS)}")


if __name__ == "__main__":
    main()
