"""
NAVTEX / SITOR-B audio WAV generator.

Encoding pipeline:
  text → CCIR-476 codes → SITOR-B FEC stream → FSK modulation → WAV file

Technical specs:
  - Character set : CCIR-476 (7-bit, constant ratio 4 marks / 3 spaces)
  - Modulation    : FSK, 100 baud
  - Mark freq     : 1700 Hz
  - Space freq    : 1530 Hz  (170 Hz shift)
  - Sample rate   : 44 100 Hz, 16-bit PCM mono
  - FEC           : SITOR-B — every 5th output symbol repeats the one 4 before it
  - Phasing       : ≥10 s idle before ZCZC, ≥2 s idle after NNNN
"""

import numpy as np
from scipy.io import wavfile

# ---------------------------------------------------------------------------
# CCIR-476 character table
# Each code is 7 bits with exactly 4 marks (1s) and 3 spaces (0s).
# Stored as (letter_char, figure_char): code_7bit.
# Control codes are keyed by name string.
# ---------------------------------------------------------------------------

# Control codes (same meaning in both modes)
CCIR476_CTRL = {
    "ALPHA": 0x0F,  # phasing signal 1 / idle (SIA)
    "REP":   0x66,  # repetition request / phasing signal 2
    "IDLE":  0x0F,  # backward-compatible alias
    "LTRS":  0x5A,  # switch to Letters mode
    "FIGS":  0x36,  # switch to Figures mode
    " ":     0x5C,  # space (both modes)
    "\r":    0x78,  # carriage return
    "\n":    0x6C,  # line feed
}

# (letter, figure, 7-bit code) triples — one entry per CCIR-476 data position
# Codes from ITU-R M.476-5 / Wikipedia CCIR-476 table
CCIR476_PAIRS = [
    ("A", "-",    0x47),
    ("B", "?",    0x72),
    ("C", ":",    0x1D),
    ("D", "\x05", 0x53),  # D / ENQ (WRU)
    ("E", "3",    0x56),
    ("F", "!",    0x1B),
    ("G", "&",    0x35),
    ("H", "#",    0x69),
    ("I", "8",    0x4D),
    ("J", "\a",   0x17),  # J / BEL
    ("K", "(",    0x1E),
    ("L", ")",    0x65),
    ("M", ".",    0x39),
    ("N", ",",    0x59),
    ("O", "9",    0x71),
    ("P", "0",    0x2D),
    ("Q", "1",    0x2E),
    ("R", "4",    0x55),
    ("S", "'",    0x4B),
    ("T", "5",    0x74),
    ("U", "7",    0x4E),
    ("V", "=",    0x3C),
    ("W", "2",    0x27),
    ("X", "/",    0x3A),
    ("Y", "6",    0x2B),
    ("Z", "+",    0x63),
]

# Build fast lookup dicts
_LETTER_TO_CODE: dict[str, int] = {}
_FIGURE_TO_CODE: dict[str, int] = {}
_CODE_TO_LETTER: dict[int, str] = {}
_CODE_TO_FIGURE: dict[int, str] = {}

for _ltr, _fig, _code in CCIR476_PAIRS:
    _LETTER_TO_CODE[_ltr] = _code
    _FIGURE_TO_CODE[_fig] = _code
    _CODE_TO_LETTER[_code] = _ltr
    _CODE_TO_FIGURE[_code] = _fig

for _ctrl_char, _ctrl_code in CCIR476_CTRL.items():
    _LETTER_TO_CODE[_ctrl_char] = _ctrl_code
    _FIGURE_TO_CODE[_ctrl_char] = _ctrl_code


# ---------------------------------------------------------------------------
# Encoding helpers
# ---------------------------------------------------------------------------

def _char_to_codes(ch: str) -> list[int]:
    """
    Return list of CCIR-476 codes needed to emit a single character,
    including any mode-shift codes (LTRS/FIGS) required before it.

    The caller must track current mode and decide whether to emit a shift;
    this function returns the sequence assuming the caller inserts shifts.
    Low-level: use text_to_ccir476() instead.
    """
    if ch in CCIR476_CTRL:
        return [CCIR476_CTRL[ch]]
    if ch.upper() in _LETTER_TO_CODE:
        return [_LETTER_TO_CODE[ch.upper()]]
    if ch in _FIGURE_TO_CODE:
        return [_FIGURE_TO_CODE[ch]]
    raise ValueError(f"Character {ch!r} not representable in CCIR-476")


def text_to_ccir476(text: str) -> list[int]:
    """
    Encode a plain-text string to a list of 7-bit CCIR-476 codes.

    Rules:
      - Input is automatically uppercased for letters.
      - LTRS / FIGS mode-shift codes are inserted as needed.
      - Unsupported characters raise ValueError.
    """
    text = text.upper()
    codes: list[int] = []
    # Start in LTRS mode (default for NAVTEX receivers after phasing)
    mode = "LTRS"

    def _need_figs(ch: str) -> bool:
        return ch not in CCIR476_CTRL and ch not in _LETTER_TO_CODE and ch in _FIGURE_TO_CODE

    def _need_ltrs(ch: str) -> bool:
        return ch not in CCIR476_CTRL and ch in _LETTER_TO_CODE and ch not in _FIGURE_TO_CODE

    for ch in text:
        if ch in CCIR476_CTRL:
            codes.append(CCIR476_CTRL[ch])
            continue

        is_letter = ch in _LETTER_TO_CODE
        is_figure = ch in _FIGURE_TO_CODE

        if not is_letter and not is_figure:
            raise ValueError(f"Character {ch!r} not representable in CCIR-476")

        # Digits / punctuation-only characters require FIGS mode
        if is_figure and not is_letter:
            if mode != "FIGS":
                codes.append(CCIR476_CTRL["FIGS"])
                mode = "FIGS"
            codes.append(_FIGURE_TO_CODE[ch])
        else:
            # Letters (and characters valid in both modes) use LTRS mode
            if mode != "LTRS":
                codes.append(CCIR476_CTRL["LTRS"])
                mode = "LTRS"
            codes.append(_LETTER_TO_CODE[ch])

    return codes


# ---------------------------------------------------------------------------
# SITOR-B Forward Error Correction
# ---------------------------------------------------------------------------

def sitor_b_fec(codes: list[int], phasing_chars: int = 143) -> list[int]:
    """
    Apply SITOR-B FEC to a list of CCIR-476 codes and prepend / append
    phasing (idle) characters.

    FEC-B rule: symbols are transmitted on two interleaved channels:
      - RX slot: symbol from a 3-symbol delay buffer
      - DX slot: current (new) symbol
    This matches the widely used NAVTEX/SITOR-B interleaver used in fielded
    encoders and avoids the garbled decode pattern seen when repeats are
    scheduled incorrectly.

    Parameters
    ----------
    codes        : Input CCIR-476 codes (data to transmit).
    phasing_chars: Number of IDLE characters prepended (default ≥10 s @
                   100 baud / 7 bits = ~143 chars per second → 143 for 1 s,
                   multiply caller-side for longer preamble).
    """
    alpha = CCIR476_CTRL["ALPHA"]
    rep = CCIR476_CTRL["REP"]

    # Phasing preamble: alternating REP and Alpha.
    preamble = [rep if i % 2 == 0 else alpha for i in range(phasing_chars)]

    # FEC-B encode using a 3-symbol delay buffer, equivalent to:
    #   emit(buffer[0]), shift buffer, load new symbol, emit(new_symbol)
    # Prime sequence with first three symbols and Alpha separators to lock
    # decoder timing before entering steady-state interleaving.
    fec_data: list[int] = []
    if len(codes) >= 3:
        b1, b2, b3 = codes[0], codes[1], codes[2]
        fec_data.extend([b1, alpha, b2, alpha, b3])
        for sym in codes[3:]:
            fec_data.append(b1)
            b1, b2, b3 = b2, b3, sym
            fec_data.append(b3)
    else:
        # Degenerate short message case
        for sym in codes:
            fec_data.extend([alpha, sym])

    # Short idle tail (≥2 s: use 30 Alpha chars)
    tail = [alpha] * 30

    return preamble + fec_data + tail


# ---------------------------------------------------------------------------
# FSK modulation
# ---------------------------------------------------------------------------

def fsk_modulate(
    codes: list[int],
    sample_rate: int = 44100,
    baud: int = 100,
    mark_hz: float = 1700.0,
    space_hz: float = 1530.0,
) -> np.ndarray:
    """
    Convert a list of CCIR-476 7-bit codes into an FSK audio signal.

    Each code is transmitted LSB-first (B1 first). A mark bit (1) uses
    mark_hz; a space bit (0) uses space_hz.

    Returns a float64 numpy array normalised to [-1.0, +1.0].
    """
    samples_per_bit = sample_rate / baud  # 441 samples at 44100 / 100
    audio_chunks: list[np.ndarray] = []
    phase = 0.0  # Maintain phase continuity across bits

    for code in codes:
        # 7 bits, LSB first
        for bit_pos in range(7):
            bit = (code >> bit_pos) & 1
            freq = mark_hz if bit else space_hz
            n_samples = int(samples_per_bit)
            t = np.arange(n_samples) / sample_rate
            # Start from current phase for continuity
            chunk = np.sin(2 * np.pi * freq * t + phase)
            # Advance phase for next chunk
            phase = (phase + 2 * np.pi * freq * n_samples / sample_rate) % (2 * np.pi)
            audio_chunks.append(chunk)

    return np.concatenate(audio_chunks)


# ---------------------------------------------------------------------------
# WAV output
# ---------------------------------------------------------------------------

def write_wav(audio: np.ndarray, filename: str, sample_rate: int = 44100) -> None:
    """Write a float64 audio array as a 16-bit PCM mono WAV file."""
    # Normalise and convert to int16
    peak = np.max(np.abs(audio))
    if peak > 0:
        audio = audio / peak
    pcm = (audio * 32767).astype(np.int16)
    wavfile.write(filename, sample_rate, pcm)


# ---------------------------------------------------------------------------
# Public API
# ---------------------------------------------------------------------------

def generate_navtex_wav(
    message_text: str,
    output_file: str,
    sample_rate: int = 44100,
    baud: int = 100,
    mark_hz: float = 1700.0,
    space_hz: float = 1530.0,
    preamble_seconds: float = 10.0,
) -> None:
    """
    Full pipeline: encode *message_text* as a NAVTEX SITOR-B FSK WAV file.

    The message must already include the ZCZC header and NNNN trailer.
    Example:
        "ZCZC KA37\\r\\nNAVAREA II...\\r\\nNNNN"

    Parameters
    ----------
    message_text    : Raw NAVTEX message string (uppercase recommended).
    output_file     : Destination .wav path.
    sample_rate     : Audio sample rate in Hz (default 44100).
    baud            : Symbol rate (default 100).
    mark_hz         : Mark (bit=1) frequency in Hz (default 1700).
    space_hz        : Space (bit=0) frequency in Hz (default 1530).
    preamble_seconds: Duration of idle phasing before ZCZC (default 10 s).
    """
    chars_per_second = baud / 7  # ~14.3 at 100 baud
    phasing_chars = int(preamble_seconds * chars_per_second)

    codes = text_to_ccir476(message_text)
    fec_stream = sitor_b_fec(codes, phasing_chars=phasing_chars)
    audio = fsk_modulate(fec_stream, sample_rate=sample_rate, baud=baud,
                         mark_hz=mark_hz, space_hz=space_hz)
    write_wav(audio, output_file, sample_rate=sample_rate)


# ---------------------------------------------------------------------------
# CLI
# ---------------------------------------------------------------------------

if __name__ == "__main__":
    import argparse
    import sys

    parser = argparse.ArgumentParser(
        description="Encode text as NAVTEX SITOR-B FSK WAV.",
        epilog="Example: python navtex_generator.py -f message -o navtex.wav",
    )
    group = parser.add_mutually_exclusive_group()
    group.add_argument(
        "-m", "--message",
        metavar="TEXT",
        help="Message text to encode (use for short one-line messages)",
    )
    group.add_argument(
        "-f", "--file",
        metavar="PATH",
        help="Read message from file (newlines normalized to \\r\\n)",
    )
    parser.add_argument(
        "-o", "--output",
        default="navtex_test.wav",
        metavar="WAV",
        help="Output WAV path (default: navtex_test.wav)",
    )
    parser.add_argument(
        "--preamble",
        type=float,
        default=10.0,
        metavar="SEC",
        help="Phasing preamble duration in seconds (default: 10)",
    )
    args = parser.parse_args()

    if args.message is not None:
        text = args.message
    elif args.file is not None:
        with open(args.file, "r", encoding="utf-8") as f:
            text = f.read()
    else:
        text = sys.stdin.read()

    # Normalize line endings to CRLF for NAVTEX
    if "\n" in text and "\r\n" not in text:
        text = text.replace("\n", "\r\n")

    generate_navtex_wav(text, args.output, preamble_seconds=args.preamble)
    print(f"Written: {args.output}")
