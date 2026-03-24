"""Tests for grib_encryptor.py"""

import json
import os
import sys
import tempfile

import pytest

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from cryptography.exceptions import InvalidTag
from cryptography.hazmat.primitives.asymmetric.ec import SECP256R1

from grib_encryptor import (
    generate_p256_keypair,
    private_key_to_hex,
    private_key_from_hex,
    ecies_encrypt,
    ecies_decrypt,
    save_ec_settings,
    load_ec_settings,
    encrypt_file,
    decrypt_file,
    _EPHEMERAL_PUBKEY_LEN,
    _NONCE_LEN,
    _TAG_LEN,
)


# ---------------------------------------------------------------------------
# Key generation
# ---------------------------------------------------------------------------

class TestGenerateP256Keypair:

    def test_returns_two_objects(self):
        priv, pub = generate_p256_keypair()
        assert priv is not None
        assert pub is not None

    def test_private_key_is_secp256r1(self):
        priv, _ = generate_p256_keypair()
        assert isinstance(priv.curve, SECP256R1)

    def test_public_key_matches_private(self):
        priv, pub = generate_p256_keypair()
        from cryptography.hazmat.primitives import serialization
        priv_pub_bytes = priv.public_key().public_bytes(
            serialization.Encoding.X962,
            serialization.PublicFormat.UncompressedPoint,
        )
        pub_bytes = pub.public_bytes(
            serialization.Encoding.X962,
            serialization.PublicFormat.UncompressedPoint,
        )
        assert priv_pub_bytes == pub_bytes

    def test_two_calls_produce_different_keys(self):
        _, pub1 = generate_p256_keypair()
        _, pub2 = generate_p256_keypair()
        from cryptography.hazmat.primitives import serialization
        b1 = pub1.public_bytes(serialization.Encoding.X962, serialization.PublicFormat.UncompressedPoint)
        b2 = pub2.public_bytes(serialization.Encoding.X962, serialization.PublicFormat.UncompressedPoint)
        assert b1 != b2


# ---------------------------------------------------------------------------
# Key serialisation
# ---------------------------------------------------------------------------

class TestPrivateKeyHex:

    def test_length_is_64(self):
        priv, _ = generate_p256_keypair()
        assert len(private_key_to_hex(priv)) == 64

    def test_is_uppercase_hex(self):
        priv, _ = generate_p256_keypair()
        hex_str = private_key_to_hex(priv)
        assert all(c in "0123456789ABCDEF" for c in hex_str)

    def test_roundtrip(self):
        priv, _ = generate_p256_keypair()
        hex_str = private_key_to_hex(priv)
        priv2 = private_key_from_hex(hex_str)
        assert private_key_to_hex(priv) == private_key_to_hex(priv2)

    def test_from_hex_wrong_length_raises(self):
        with pytest.raises(ValueError):
            private_key_from_hex("AABB")

    def test_from_hex_reconstructs_valid_key(self):
        priv, _ = generate_p256_keypair()
        hex_str = private_key_to_hex(priv)
        priv2 = private_key_from_hex(hex_str)
        assert isinstance(priv2.curve, SECP256R1)


# ---------------------------------------------------------------------------
# ECIES encrypt / decrypt
# ---------------------------------------------------------------------------

class TestEciesEncryptDecrypt:

    def test_roundtrip_small(self):
        priv, pub = generate_p256_keypair()
        plaintext = b"Hello, NAVTEX!"
        ciphertext = ecies_encrypt(pub, plaintext)
        assert ecies_decrypt(priv, ciphertext) == plaintext

    def test_roundtrip_empty(self):
        priv, pub = generate_p256_keypair()
        plaintext = b""
        ciphertext = ecies_encrypt(pub, plaintext)
        assert ecies_decrypt(priv, ciphertext) == plaintext

    def test_output_length(self):
        priv, pub = generate_p256_keypair()
        plaintext = b"A" * 100
        ciphertext = ecies_encrypt(pub, plaintext)
        expected = _EPHEMERAL_PUBKEY_LEN + _NONCE_LEN + _TAG_LEN + len(plaintext)
        assert len(ciphertext) == expected

    def test_encrypt_twice_different_ciphertext(self):
        """Each encryption uses a fresh ephemeral key and nonce."""
        _, pub = generate_p256_keypair()
        plaintext = b"same message"
        ct1 = ecies_encrypt(pub, plaintext)
        ct2 = ecies_encrypt(pub, plaintext)
        assert ct1 != ct2

    def test_wrong_private_key_raises(self):
        _, pub = generate_p256_keypair()
        wrong_priv, _ = generate_p256_keypair()
        ciphertext = ecies_encrypt(pub, b"secret")
        with pytest.raises(Exception):
            ecies_decrypt(wrong_priv, ciphertext)

    def test_tampered_ciphertext_raises(self):
        priv, pub = generate_p256_keypair()
        ciphertext = ecies_encrypt(pub, b"secret data")
        tampered = bytearray(ciphertext)
        tampered[-1] ^= 0xFF  # flip last byte of ciphertext
        with pytest.raises(InvalidTag):
            ecies_decrypt(priv, bytes(tampered))

    def test_tampered_tag_raises(self):
        priv, pub = generate_p256_keypair()
        ciphertext = ecies_encrypt(pub, b"secret data")
        tampered = bytearray(ciphertext)
        # Tag is at bytes [65+12 : 65+12+16]
        tag_start = _EPHEMERAL_PUBKEY_LEN + _NONCE_LEN
        tampered[tag_start] ^= 0x01
        with pytest.raises(InvalidTag):
            ecies_decrypt(priv, bytes(tampered))

    def test_too_short_raises(self):
        priv, _ = generate_p256_keypair()
        with pytest.raises(ValueError):
            ecies_decrypt(priv, b"\x00" * 10)

    def test_large_binary_roundtrip(self):
        """Simulate a GRIB2-sized payload (20 KB)."""
        import os
        priv, pub = generate_p256_keypair()
        plaintext = os.urandom(20 * 1024)
        ciphertext = ecies_encrypt(pub, plaintext)
        assert ecies_decrypt(priv, ciphertext) == plaintext


# ---------------------------------------------------------------------------
# EC settings persistence
# ---------------------------------------------------------------------------

class TestEcSettings:

    def test_save_creates_json(self):
        priv, pub = generate_p256_keypair()
        with tempfile.NamedTemporaryFile(suffix=".json", delete=False) as f:
            path = f.name
        try:
            save_ec_settings(priv, pub, path)
            assert os.path.isfile(path)
            with open(path) as fh:
                data = json.load(fh)
            for key in ("curve", "private_key_hex", "private_key_pem", "public_key_pem", "public_key_compressed_hex"):
                assert key in data, f"Missing key: {key}"
        finally:
            os.unlink(path)

    def test_roundtrip(self):
        priv, pub = generate_p256_keypair()
        with tempfile.NamedTemporaryFile(suffix=".json", delete=False) as f:
            path = f.name
        try:
            save_ec_settings(priv, pub, path)
            priv2, pub2 = load_ec_settings(path)
            assert private_key_to_hex(priv) == private_key_to_hex(priv2)
        finally:
            os.unlink(path)

    def test_curve_field(self):
        priv, pub = generate_p256_keypair()
        with tempfile.NamedTemporaryFile(suffix=".json", delete=False) as f:
            path = f.name
        try:
            save_ec_settings(priv, pub, path)
            with open(path) as fh:
                data = json.load(fh)
            assert data["curve"] == "secp256r1"
        finally:
            os.unlink(path)


# ---------------------------------------------------------------------------
# File-level helpers
# ---------------------------------------------------------------------------

class TestFileHelpers:

    def test_encrypt_decrypt_file_roundtrip(self):
        priv, pub = generate_p256_keypair()
        plaintext = b"GRIB2 binary data\x00\x01\x02" * 100

        with (
            tempfile.NamedTemporaryFile(delete=False) as src_f,
            tempfile.NamedTemporaryFile(delete=False) as enc_f,
            tempfile.NamedTemporaryFile(delete=False) as dec_f,
        ):
            src_path = src_f.name
            enc_path = enc_f.name
            dec_path = dec_f.name

        try:
            with open(src_path, "wb") as fh:
                fh.write(plaintext)
            encrypt_file(pub, src_path, enc_path)
            decrypt_file(priv, enc_path, dec_path)
            with open(dec_path, "rb") as fh:
                result = fh.read()
            assert result == plaintext
        finally:
            for p in (src_path, enc_path, dec_path):
                try:
                    os.unlink(p)
                except FileNotFoundError:
                    pass
