"""
Elliptic Curve Integrated Encryption Scheme (ECIES) for binary files.

Curve  : P-256 (secp256r1 / NIST P-256)
KEM    : Ephemeral ECDH
KDF    : HKDF-SHA256
Cipher : AES-256-GCM (authenticated encryption)

Ciphertext wire format (all fields concatenated, no length delimiters):
  [ 65 bytes  ] uncompressed ephemeral public key (0x04 || X || Y)
  [ 12 bytes  ] AES-GCM nonce
  [ 16 bytes  ] AES-GCM authentication tag
  [ N  bytes  ] ciphertext (same length as plaintext)

Private-key serialisation for NAVTEX embedding:
  Raw 32-byte scalar encoded as 64 uppercase hex characters.
"""

import json
import os

from cryptography.hazmat.primitives.asymmetric import ec
from cryptography.hazmat.primitives.asymmetric.ec import (
    SECP256R1,
    EllipticCurvePrivateKey,
    EllipticCurvePublicKey,
)
from cryptography.hazmat.primitives.ciphers.aead import AESGCM
from cryptography.hazmat.primitives.hashes import SHA256
from cryptography.hazmat.primitives.kdf.hkdf import HKDF
from cryptography.hazmat.primitives import serialization

# HKDF info label — used on both encrypt and decrypt sides
_HKDF_INFO = b"NAVTEX-GRIB-ECIES-P256"
_NONCE_LEN = 12
_TAG_LEN = 16
_EPHEMERAL_PUBKEY_LEN = 65  # uncompressed point


# ---------------------------------------------------------------------------
# Key generation
# ---------------------------------------------------------------------------

def generate_p256_keypair() -> tuple[EllipticCurvePrivateKey, EllipticCurvePublicKey]:
    """Generate a fresh P-256 key pair."""
    private_key = ec.generate_private_key(SECP256R1())
    return private_key, private_key.public_key()


# ---------------------------------------------------------------------------
# Key serialisation helpers
# ---------------------------------------------------------------------------

def private_key_to_hex(private_key: EllipticCurvePrivateKey) -> str:
    """Return the raw 32-byte private scalar as 64 uppercase hex characters."""
    raw = private_key.private_numbers().private_value
    return format(raw, "064X")


def private_key_from_hex(hex_str: str) -> EllipticCurvePrivateKey:
    """Reconstruct a P-256 private key from a 64-char hex scalar string."""
    if len(hex_str) != 64:
        raise ValueError("Expected 64 hex characters for P-256 private key scalar")
    scalar = int(hex_str, 16)
    return ec.derive_private_key(scalar, SECP256R1())


def public_key_to_hex(public_key: EllipticCurvePublicKey) -> str:
    """Return the public key as 66-char compressed point hex (02/03 prefix + 32-byte x)."""
    raw = public_key.public_bytes(
        encoding=serialization.Encoding.X962,
        format=serialization.PublicFormat.CompressedPoint,
    )
    return raw.hex().upper()


def _pubkey_to_bytes(public_key: EllipticCurvePublicKey) -> bytes:
    """Serialise a public key as an uncompressed 65-byte point."""
    return public_key.public_bytes(
        encoding=serialization.Encoding.X962,
        format=serialization.PublicFormat.UncompressedPoint,
    )


def _pubkey_from_bytes(data: bytes) -> EllipticCurvePublicKey:
    """Deserialise an uncompressed 65-byte point to a public key."""
    return ec.EllipticCurvePublicKey.from_encoded_point(SECP256R1(), data)


# ---------------------------------------------------------------------------
# ECIES encrypt / decrypt
# ---------------------------------------------------------------------------

def ecies_encrypt(public_key: EllipticCurvePublicKey, plaintext: bytes) -> bytes:
    """
    Encrypt *plaintext* for *public_key* using ECIES / AES-256-GCM.

    Returns raw ciphertext bytes in the wire format described in the module
    docstring.
    """
    # 1. Generate ephemeral key pair
    ephemeral_private = ec.generate_private_key(SECP256R1())
    ephemeral_public = ephemeral_private.public_key()

    # 2. ECDH shared secret
    shared_secret = ephemeral_private.exchange(ec.ECDH(), public_key)

    # 3. KDF → 32-byte AES key
    aes_key = HKDF(
        algorithm=SHA256(),
        length=32,
        salt=None,
        info=_HKDF_INFO,
    ).derive(shared_secret)

    # 4. AES-256-GCM encrypt
    nonce = os.urandom(_NONCE_LEN)
    aesgcm = AESGCM(aes_key)
    ct_with_tag = aesgcm.encrypt(nonce, plaintext, associated_data=None)
    # cryptography library appends the tag at the end of ct_with_tag
    ciphertext = ct_with_tag[:-_TAG_LEN]
    tag = ct_with_tag[-_TAG_LEN:]

    # 5. Wire format
    return _pubkey_to_bytes(ephemeral_public) + nonce + tag + ciphertext


def ecies_decrypt(private_key: EllipticCurvePrivateKey, data: bytes) -> bytes:
    """
    Decrypt ECIES-encrypted bytes produced by :func:`ecies_encrypt`.

    Raises ``cryptography.exceptions.InvalidTag`` if the ciphertext has been
    tampered with (GCM authentication failure).
    """
    if len(data) < _EPHEMERAL_PUBKEY_LEN + _NONCE_LEN + _TAG_LEN:
        raise ValueError("Ciphertext too short to be valid ECIES output")

    # 1. Parse wire format
    offset = 0
    ephemeral_pub_bytes = data[offset: offset + _EPHEMERAL_PUBKEY_LEN]
    offset += _EPHEMERAL_PUBKEY_LEN
    nonce = data[offset: offset + _NONCE_LEN]
    offset += _NONCE_LEN
    tag = data[offset: offset + _TAG_LEN]
    offset += _TAG_LEN
    ciphertext = data[offset:]

    # 2. Reconstruct ephemeral public key and perform ECDH
    ephemeral_public = _pubkey_from_bytes(ephemeral_pub_bytes)
    shared_secret = private_key.exchange(ec.ECDH(), ephemeral_public)

    # 3. KDF
    aes_key = HKDF(
        algorithm=SHA256(),
        length=32,
        salt=None,
        info=_HKDF_INFO,
    ).derive(shared_secret)

    # 4. AES-256-GCM decrypt (raises InvalidTag on tamper)
    aesgcm = AESGCM(aes_key)
    return aesgcm.decrypt(nonce, ciphertext + tag, associated_data=None)


# ---------------------------------------------------------------------------
# Persistence helpers
# ---------------------------------------------------------------------------

def save_ec_settings(
    private_key: EllipticCurvePrivateKey,
    public_key: EllipticCurvePublicKey,
    path: str,
) -> None:
    """
    Persist EC key material to a JSON file (private — not given to players).

    Saved fields:
      curve                    : curve name string
      private_key_hex          : 64-char uppercase hex scalar (NAVTEX payload)
      private_key_pem          : PKCS8 PEM (unencrypted)
      public_key_pem           : SubjectPublicKeyInfo PEM
      public_key_compressed_hex: 66-char compressed point hex
    """
    priv_pem = private_key.private_bytes(
        encoding=serialization.Encoding.PEM,
        format=serialization.PrivateFormat.PKCS8,
        encryption_algorithm=serialization.NoEncryption(),
    ).decode()

    pub_pem = public_key.public_bytes(
        encoding=serialization.Encoding.PEM,
        format=serialization.PublicFormat.SubjectPublicKeyInfo,
    ).decode()

    pub_compressed = public_key.public_bytes(
        encoding=serialization.Encoding.X962,
        format=serialization.PublicFormat.CompressedPoint,
    ).hex().upper()

    settings = {
        "curve": "secp256r1",
        "private_key_hex": private_key_to_hex(private_key),
        "private_key_pem": priv_pem,
        "public_key_pem": pub_pem,
        "public_key_compressed_hex": pub_compressed,
    }

    with open(path, "w") as fh:
        json.dump(settings, fh, indent=2)


def load_ec_settings(path: str) -> tuple[EllipticCurvePrivateKey, EllipticCurvePublicKey]:
    """Load key material from a JSON file created by :func:`save_ec_settings`."""
    with open(path) as fh:
        settings = json.load(fh)
    private_key = private_key_from_hex(settings["private_key_hex"])
    return private_key, private_key.public_key()


# ---------------------------------------------------------------------------
# File-level helpers
# ---------------------------------------------------------------------------

def encrypt_file(public_key: EllipticCurvePublicKey, src: str, dst: str) -> None:
    """Encrypt the file at *src* and write the ECIES blob to *dst*."""
    with open(src, "rb") as fh:
        plaintext = fh.read()
    ciphertext = ecies_encrypt(public_key, plaintext)
    with open(dst, "wb") as fh:
        fh.write(ciphertext)


def decrypt_file(private_key: EllipticCurvePrivateKey, src: str, dst: str) -> None:
    """Decrypt the ECIES blob at *src* and write plaintext to *dst*."""
    with open(src, "rb") as fh:
        data = fh.read()
    plaintext = ecies_decrypt(private_key, data)
    with open(dst, "wb") as fh:
        fh.write(plaintext)


# ---------------------------------------------------------------------------
# Quick self-test
# ---------------------------------------------------------------------------

if __name__ == "__main__":
    priv, pub = generate_p256_keypair()
    hex_key = private_key_to_hex(priv)
    print(f"Private key (hex): {hex_key}")

    msg = b"Hello, NAVTEX world!"
    enc = ecies_encrypt(pub, msg)
    dec = ecies_decrypt(priv, enc)
    assert dec == msg, "Decryption mismatch!"
    print("ECIES round-trip: OK")

    # Verify scalar round-trip
    priv2 = private_key_from_hex(hex_key)
    dec2 = ecies_decrypt(priv2, enc)
    assert dec2 == msg, "Key reconstruction mismatch!"
    print("Key hex round-trip: OK")
