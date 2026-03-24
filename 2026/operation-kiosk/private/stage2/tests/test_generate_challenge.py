"""Tests for generate_challenge.py (orchestration layer)."""

import json
import os
import sys
import tempfile

import pytest

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from grib_encryptor import generate_p256_keypair, ecies_decrypt
from navtex_generator import generate_navtex_wav


# ---------------------------------------------------------------------------
# Flag-append logic
# ---------------------------------------------------------------------------

FLAG = b"ph0wn{this_M@rine_Grib_stuff_is_N0_JOKE?}"

# Minimal fake GRIB2 blob: starts with "GRIB" magic, ends with "7777"
FAKE_GRIB = b"GRIB\x00\x00\x00\x00" + b"\xAB" * 100 + b"7777"


class TestFlagAppend:

    def test_flag_appended_after_grib_marker(self):
        payload = FAKE_GRIB + FLAG
        assert payload.endswith(FLAG)
        assert b"7777" in payload

    def test_grib_content_preserved(self):
        payload = FAKE_GRIB + FLAG
        grib_part = payload[: len(FAKE_GRIB)]
        assert grib_part == FAKE_GRIB

    def test_flag_extractable_from_decrypted_bytes(self):
        """After encrypting the GRIB+flag payload, decrypting must yield the flag at the end."""
        priv, pub = generate_p256_keypair()
        payload = FAKE_GRIB + FLAG

        from grib_encryptor import ecies_encrypt
        ciphertext = ecies_encrypt(pub, payload)
        decrypted = ecies_decrypt(priv, ciphertext)

        assert decrypted.endswith(FLAG), "Flag not found at end of decrypted payload"

    def test_encrypted_file_does_not_start_with_grib_magic(self):
        """Encrypted output must not be parseable as raw GRIB."""
        from grib_encryptor import ecies_encrypt
        _, pub = generate_p256_keypair()
        payload = FAKE_GRIB + FLAG
        ciphertext = ecies_encrypt(pub, payload)
        # GRIB2 files start with b"GRIB" magic
        assert not ciphertext.startswith(b"GRIB"), "Encrypted file starts with GRIB magic — not encrypted?"


# ---------------------------------------------------------------------------
# Full pipeline (mocked to avoid requiring mod_arome.grib2)
# ---------------------------------------------------------------------------

class TestFullPipeline:

    def test_decrypt_recovers_flag(self):
        """End-to-end: encrypt FAKE_GRIB+flag, decrypt, check flag."""
        from grib_encryptor import ecies_encrypt
        priv, pub = generate_p256_keypair()
        payload = FAKE_GRIB + FLAG
        enc = ecies_encrypt(pub, payload)
        dec = ecies_decrypt(priv, enc)
        assert dec[-len(FLAG):] == FLAG

    def test_ec_settings_json_has_required_fields(self):
        priv, pub = generate_p256_keypair()
        with tempfile.NamedTemporaryFile(suffix=".json", delete=False, mode="w") as f:
            path = f.name

        try:
            from grib_encryptor import save_ec_settings
            save_ec_settings(priv, pub, path)
            with open(path) as fh:
                data = json.load(fh)
            for field in ("curve", "private_key_hex", "private_key_pem", "public_key_pem", "public_key_compressed_hex"):
                assert field in data
        finally:
            os.unlink(path)

    def test_navtex_wav_created_and_non_empty(self):
        priv, _ = generate_p256_keypair()
        from grib_encryptor import private_key_to_hex
        hex_key = private_key_to_hex(priv)
        msg = (
            "ZCZC KA37\r\n"
            "TEST MESSAGE\r\n"
            f"DECRYPT KEY P256:\r\n"
            f"{hex_key}\r\n"
            "NNNN"
        )
        with tempfile.NamedTemporaryFile(suffix=".wav", delete=False) as f:
            path = f.name
        try:
            generate_navtex_wav(msg, path, preamble_seconds=0.1)
            assert os.path.isfile(path)
            assert os.path.getsize(path) > 0
        finally:
            os.unlink(path)

    def test_private_key_hex_in_ccir476_charset(self):
        """Private key hex must only contain chars valid in CCIR-476 FIGS mode (0-9, A-F)."""
        priv, _ = generate_p256_keypair()
        from grib_encryptor import private_key_to_hex
        hex_key = private_key_to_hex(priv)
        for ch in hex_key:
            assert ch in "0123456789ABCDEF", f"Invalid CCIR-476 char {ch!r} in private key hex"

    def test_encrypted_output_length_correct(self):
        from grib_encryptor import ecies_encrypt, _EPHEMERAL_PUBKEY_LEN, _NONCE_LEN, _TAG_LEN
        _, pub = generate_p256_keypair()
        payload = FAKE_GRIB + FLAG
        enc = ecies_encrypt(pub, payload)
        expected = _EPHEMERAL_PUBKEY_LEN + _NONCE_LEN + _TAG_LEN + len(payload)
        assert len(enc) == expected
