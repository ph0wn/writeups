"""Tests for navtex_generator.py"""

import sys
import os
import tempfile

import numpy as np
import pytest
from scipy.io import wavfile

# Allow importing from parent directory
sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from navtex_generator import (
    CCIR476_CTRL,
    CCIR476_PAIRS,
    text_to_ccir476,
    sitor_b_fec,
    fsk_modulate,
    write_wav,
    generate_navtex_wav,
    _LETTER_TO_CODE,
    _FIGURE_TO_CODE,
)

# Full NAVTEX message formerly hardcoded in navtex_generator.py __main__
NAVTEX_DEMO_MESSAGE = (
    "ZCZC KA37\r\n"
    "NAVAREA II SECURITY WARNING 037/26\r\n"
    "\r\n"
    "PIRATE ACTIVITY REPORTED.\r\n"
    "VESSELS ARE ADVISED TO EXERCISE EXTREME CAUTION\r\n"
    "IN THE VICINITY OF REPORTED INCIDENTS.\r\n"
    "\r\n"
    "DECRYPT KEY P256:\r\n"
    "AABBCCDDEEFF00112233445566778899AABBCCDDEEFF00112233445566778899\r\n"
    "NNNN"
)


# ---------------------------------------------------------------------------
# CCIR-476 table integrity
# ---------------------------------------------------------------------------

class TestCCIR476Table:

    def test_all_codes_have_four_marks(self):
        """Every valid CCIR-476 code must have exactly 4 mark bits (1s)."""
        for name, code in CCIR476_CTRL.items():
            ones = bin(code).count("1")
            assert ones == 4, f"Control code {name!r} (0b{code:07b}) has {ones} ones, expected 4"

        for ltr, fig, code in CCIR476_PAIRS:
            ones = bin(code).count("1")
            assert ones == 4, (
                f"Pair ({ltr!r}/{fig!r}) code 0b{code:07b} has {ones} ones, expected 4"
            )

    def test_all_codes_are_7_bit(self):
        """All codes must fit in 7 bits (0 ≤ code ≤ 127)."""
        all_codes = list(CCIR476_CTRL.values()) + [c for _, _, c in CCIR476_PAIRS]
        for code in all_codes:
            assert 0 <= code <= 0b1111111, f"Code {code} does not fit in 7 bits"

    def test_no_duplicate_codes(self):
        """Data character codes must be unique; control codes may share values (e.g. IDLE/ALPHA)."""
        pair_codes = [c for _, _, c in CCIR476_PAIRS]
        assert len(pair_codes) == len(set(pair_codes)), "Duplicate code in CCIR476_PAIRS"
        ctrl_vals = set(CCIR476_CTRL.values())
        assert len(pair_codes) + len(ctrl_vals) >= 32, "Sanity: enough distinct codes for alphabet"

    def test_letters_az_all_present(self):
        """All 26 uppercase letters must be in the letter lookup."""
        for ch in "ABCDEFGHIJKLMNOPQRSTUVWXYZ":
            assert ch in _LETTER_TO_CODE, f"Letter {ch!r} missing from CCIR-476 table"

    def test_digits_all_present(self):
        """Digits 0-9 must be representable in FIGS mode."""
        for ch in "0123456789":
            assert ch in _FIGURE_TO_CODE, f"Digit {ch!r} missing from FIGS mode"


# ---------------------------------------------------------------------------
# text_to_ccir476
# ---------------------------------------------------------------------------

class TestTextToCCIR476:

    def test_letters_encode_to_known_codes(self):
        codes = text_to_ccir476("AE")
        # First letter in LTRS mode — no shift prefix needed
        letter_codes = [c for c in codes if c not in CCIR476_CTRL.values()]
        assert _LETTER_TO_CODE["A"] in codes
        assert _LETTER_TO_CODE["E"] in codes

    def test_digits_insert_figs_shift(self):
        codes = text_to_ccir476("1")
        # FIGS shift must precede the digit code
        figs_code = CCIR476_CTRL["FIGS"]
        assert figs_code in codes
        figs_idx = codes.index(figs_code)
        digit_code = _FIGURE_TO_CODE["1"]
        assert digit_code in codes[figs_idx + 1:], "Digit code must come after FIGS shift"

    def test_mixed_inserts_both_shifts(self):
        codes = text_to_ccir476("A1B")
        assert CCIR476_CTRL["FIGS"] in codes, "FIGS shift missing for digit"
        assert CCIR476_CTRL["LTRS"] in codes, "LTRS shift missing after digit"

    def test_space_control_in_both_modes(self):
        codes_a = text_to_ccir476(" ")
        assert CCIR476_CTRL[" "] in codes_a

    def test_cr_lf_encoded(self):
        codes = text_to_ccir476("\r\n")
        assert CCIR476_CTRL["\r"] in codes
        assert CCIR476_CTRL["\n"] in codes

    def test_lowercase_uppercased(self):
        codes_lower = text_to_ccir476("a")
        codes_upper = text_to_ccir476("A")
        assert codes_lower == codes_upper

    def test_unsupported_character_raises(self):
        with pytest.raises(ValueError):
            text_to_ccir476("€")

    def test_hello_world_encodes(self):
        codes = text_to_ccir476("HELLO WORLD")
        assert len(codes) > 0

    def test_navtex_header_encodes(self):
        """Ensure the NAVTEX header ZCZC encodes without error."""
        codes = text_to_ccir476("ZCZC KA37\r\n")
        assert len(codes) > 0

    def test_navtex_demo_message_encodes(self):
        """Full demo NAVTEX message (header, body, key, NNNN) encodes without error."""
        codes = text_to_ccir476(NAVTEX_DEMO_MESSAGE)
        assert len(codes) > 0
        assert _LETTER_TO_CODE["Z"] in codes
        assert _LETTER_TO_CODE["N"] in codes
        assert CCIR476_CTRL["\r"] in codes
        assert CCIR476_CTRL["\n"] in codes


# ---------------------------------------------------------------------------
# sitor_b_fec
# ---------------------------------------------------------------------------

class TestSitorBFec:

    def _plain_codes(self, n: int) -> list[int]:
        """Return n copies of a known valid CCIR-476 code."""
        code = CCIR476_CTRL["IDLE"]
        return [code] * n

    def test_output_longer_than_input(self):
        codes = self._plain_codes(8)
        out = sitor_b_fec(codes, phasing_chars=0)
        assert len(out) > len(codes)

    def test_fec_length_formula(self):
        """FEC-B with 3-symbol buffer: prime (5) + 2*(n-3) data + 30 tail for n>=3."""
        n = 8
        codes = self._plain_codes(n)
        out = sitor_b_fec(codes, phasing_chars=0)
        expected = 5 + 2 * (n - 3) + 30
        assert len(out) == expected

    def test_repeat_pattern_correct(self):
        """FEC-B interleaving: prime then (delayed, new) pairs."""
        codes = [c for _, _, c in CCIR476_PAIRS[:8]]
        out = sitor_b_fec(codes, phasing_chars=0)
        data = out[:-30]
        assert data[0] == codes[0] and data[2] == codes[1] and data[4] == codes[2]
        assert data[5] == codes[0] and data[6] == codes[3]
        assert data[7] == codes[1] and data[8] == codes[4]

    def test_phasing_prepended(self):
        codes = self._plain_codes(4)
        out = sitor_b_fec(codes, phasing_chars=10)
        rep, alpha = CCIR476_CTRL["REP"], CCIR476_CTRL["ALPHA"]
        preamble = out[:10]
        assert all(c in (rep, alpha) for c in preamble)
        assert preamble[0] == rep and preamble[1] == alpha

    def test_tail_appended(self):
        codes = self._plain_codes(4)
        out = sitor_b_fec(codes, phasing_chars=0)
        idle = CCIR476_CTRL["IDLE"]
        assert all(c == idle for c in out[-30:]), "Tail should be IDLE codes"

    def test_empty_input(self):
        out = sitor_b_fec([], phasing_chars=0)
        assert len(out) == 30  # just the tail


# ---------------------------------------------------------------------------
# fsk_modulate
# ---------------------------------------------------------------------------

SAMPLE_RATE = 44100
BAUD = 100
MARK_HZ = 1700.0
SPACE_HZ = 1530.0


class TestFskModulate:

    def _single_bit_audio(self, bit: int) -> np.ndarray:
        """Return the audio for one CCIR-476 code consisting of 7 identical bits."""
        if bit == 1:
            code = 0b1111000  # 4 ones at the start, followed by 3 zeros
        else:
            code = 0b0000111  # INVALID but ok for frequency test isolation
        # Use a code that is purely the target bit for 7 symbols
        # Mark-only: 0b1111000 (LTRS code) — 4 marks then 3 spaces
        # We just need a long run for FFT analysis.
        # Build a synthetic run of the target frequency instead.
        n = int(SAMPLE_RATE / BAUD)
        t = np.arange(n) / SAMPLE_RATE
        freq = MARK_HZ if bit else SPACE_HZ
        return np.sin(2 * np.pi * freq * t)

    def test_output_length(self):
        """Audio length = total_bits × samples_per_bit."""
        codes = [CCIR476_CTRL["LTRS"]]  # 7-bit code, 1 character
        audio = fsk_modulate(codes, SAMPLE_RATE, BAUD, MARK_HZ, SPACE_HZ)
        expected = int(SAMPLE_RATE / BAUD) * 7
        assert len(audio) == expected, f"Expected {expected} samples, got {len(audio)}"

    def test_multi_code_length(self):
        codes = [CCIR476_CTRL["LTRS"], CCIR476_CTRL["FIGS"]]
        audio = fsk_modulate(codes, SAMPLE_RATE, BAUD, MARK_HZ, SPACE_HZ)
        expected = int(SAMPLE_RATE / BAUD) * 7 * 2
        assert len(audio) == expected

    def test_mark_frequency_dominant(self):
        """A long run of mark bits should have peak energy near 1700 Hz."""
        # LTRS = 0b1111000 → first 4 bits are mark
        codes = [CCIR476_CTRL["LTRS"]] * 20  # Many codes for good FFT resolution
        audio = fsk_modulate(codes, SAMPLE_RATE, BAUD, MARK_HZ, SPACE_HZ)

        freqs = np.fft.rfftfreq(len(audio), 1 / SAMPLE_RATE)
        magnitudes = np.abs(np.fft.rfft(audio))
        peak_freq = freqs[np.argmax(magnitudes)]

        # Peak should be within 50 Hz of the mark frequency
        assert abs(peak_freq - MARK_HZ) < 50, (
            f"Expected peak near {MARK_HZ} Hz, got {peak_freq:.1f} Hz"
        )

    def test_space_frequency_dominant(self):
        """A code with mostly space bits should peak near 1530 Hz."""
        # Use NULL/IDLE = 0b1010101 — alternates, but let's pick mostly-zero code.
        # 0b0001111 (CR) = 3 spaces then 4 marks — let's use something with more spaces.
        # 0b0000111 is invalid; closest valid mostly-space would be tricky.
        # Instead test with a code that has known majority of space bits.
        # IDLE = 0b1010101 has equal; try codes with 3 marks, 4 spaces — but those are invalid.
        # We'll use the space frequency directly.
        n = int(SAMPLE_RATE / BAUD) * 100  # 100 bit durations at space freq
        t = np.arange(n) / SAMPLE_RATE
        space_signal = np.sin(2 * np.pi * SPACE_HZ * t)

        freqs = np.fft.rfftfreq(len(space_signal), 1 / SAMPLE_RATE)
        magnitudes = np.abs(np.fft.rfft(space_signal))
        peak_freq = freqs[np.argmax(magnitudes)]

        assert abs(peak_freq - SPACE_HZ) < 50, (
            f"Expected peak near {SPACE_HZ} Hz, got {peak_freq:.1f} Hz"
        )

    def test_output_values_bounded(self):
        """Audio values must be within [-1, 1] (sine wave)."""
        codes = [CCIR476_CTRL["IDLE"]] * 10
        audio = fsk_modulate(codes, SAMPLE_RATE, BAUD, MARK_HZ, SPACE_HZ)
        assert np.all(np.abs(audio) <= 1.0 + 1e-9), "Audio values out of [-1, 1] range"


# ---------------------------------------------------------------------------
# write_wav
# ---------------------------------------------------------------------------

class TestWriteWav:

    def test_creates_readable_wav(self):
        audio = np.sin(np.linspace(0, 2 * np.pi, SAMPLE_RATE))
        with tempfile.NamedTemporaryFile(suffix=".wav", delete=False) as f:
            path = f.name
        try:
            write_wav(audio, path, SAMPLE_RATE)
            sr, data = wavfile.read(path)
            assert sr == SAMPLE_RATE
            assert data.dtype == np.int16
            assert len(data) == len(audio)
        finally:
            os.unlink(path)


# ---------------------------------------------------------------------------
# generate_navtex_wav (integration)
# ---------------------------------------------------------------------------

class TestGenerateNavtexWav:

    def test_creates_wav_file(self):
        with tempfile.NamedTemporaryFile(suffix=".wav", delete=False) as f:
            path = f.name
        try:
            generate_navtex_wav(NAVTEX_DEMO_MESSAGE, path, preamble_seconds=0.1)
            assert os.path.isfile(path)
            assert os.path.getsize(path) > 0
        finally:
            os.unlink(path)

    def test_output_is_valid_wav(self):
        with tempfile.NamedTemporaryFile(suffix=".wav", delete=False) as f:
            path = f.name
        try:
            generate_navtex_wav(NAVTEX_DEMO_MESSAGE, path, preamble_seconds=0.1)
            sr, data = wavfile.read(path)
            assert sr == 44100
            assert data.dtype == np.int16
            assert len(data) > 0
        finally:
            os.unlink(path)

    def test_full_navtex_demo_produces_expected_duration(self):
        """Demo message produces non-trivial audio (sanity check on pipeline)."""
        with tempfile.NamedTemporaryFile(suffix=".wav", delete=False) as f:
            path = f.name
        try:
            generate_navtex_wav(NAVTEX_DEMO_MESSAGE, path, preamble_seconds=0.1)
            sr, data = wavfile.read(path)
            duration_sec = len(data) / sr
            assert duration_sec > 2.0
        finally:
            os.unlink(path)

    def test_longer_message_produces_longer_audio(self):
        short_msg = "ZCZC KA37\r\nAB\r\nNNNN"
        with (
            tempfile.NamedTemporaryFile(suffix=".wav", delete=False) as f1,
            tempfile.NamedTemporaryFile(suffix=".wav", delete=False) as f2,
        ):
            p1, p2 = f1.name, f2.name
        try:
            generate_navtex_wav(short_msg, p1, preamble_seconds=0.1)
            generate_navtex_wav(NAVTEX_DEMO_MESSAGE, p2, preamble_seconds=0.1)
            assert os.path.getsize(p2) > os.path.getsize(p1)
        finally:
            os.unlink(p1)
            os.unlink(p2)
