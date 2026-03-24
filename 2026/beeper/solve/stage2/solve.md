# Stage 2 - Solution

## Introduction

Stage 2 builds directly on Stage 1: completing it is a prerequisite. The hidden command `pico_rum`, uncovered by reversing the firmware in Stage 1, is the entry point for this challenge. Sending it to the device over radio triggers a secret feature, and the flag is displayed on the screen.

Where Stage 1 was a pure static reverse engineering exercise, Stage 2 is a pure radio challenge: the goal is to reconstruct and transmit the correct radio signal encoding the `pico_rum` command.

This stage can be approached in two ways: using a dedicated radio chip paired with a microcontroller (e.g. a CC1101 breakout driven by an Arduino), or using an SDR with transmission capability. This solution uses a **BladeRF**, a full-duplex SDR that can both receive and transmit signals.

## Reconstructing the Radio Protocol

### Step 1 — Extracting the protocol parameters from the PDF

The stolen PDF document describes the radio characteristics of the beeper in its *Technical Specifications* section. This is the primary source for all protocol parameters:

- **Modulation**: OOK (On-Off Keying) at **433.92 MHz**
- **Encoding**: PWM (Pulse Width Modulation) with a fixed bit period of **1212 µs**
- **Radio module**: CC1101 in asynchronous serial mode (`IOCFG0 = 0x0D`)
- **Bit encoding**:

| Pulse type | Duration | Meaning |
|------------|----------|---------|
| Short pulse | 376 µs | Bit **1** |
| Long pulse | 780 µs | Bit **0** |
| Sync pulse | 2209 µs | Message delimiter |

- **Data format**: ASCII, **MSB first**, repeated **2 times** (the firmware validates a command only if both copies are identical)

The *Secret Feature* section of the PDF is redacted. The command itself (`pico_rum`) was recovered in Stage 1 by reversing the firmware. All other parameters needed to transmit it are fully documented in the PDF.

### Step 2 — Understanding OOK and PWM encoding

Before building the signal, it helps to understand what the PDF parameters actually mean.

**OOK (On-Off Keying)** is the simplest form of amplitude modulation: the radio carrier is either fully ON or fully OFF. There is no frequency or phase shift, just the presence or absence of a signal.

**PWM (Pulse Width Modulation)** encodes each bit as a pulse (carrier ON) followed by a gap (carrier OFF), where the total duration of pulse+gap is always fixed. The *width* of the pulse alone determines the bit value:

```
|<---- 1212 µs ---->|
|  pulse  |   gap   |
|▓▓▓▓▓▓▓▓▓|         |
```

A short pulse encodes a bit 1, a long pulse encodes a bit 0. The PDF only gives pulse durations; the gap is deduced as `gap = 1212 - pulse_duration`:

| Bit | Pulse (ON) | Gap (OFF)               | Total   |
|-----|------------|-------------------------|---------|
| 1   | 376 µs     | 1212 - 376 = **836 µs** | 1212 µs |
| 0   | 780 µs     | 1212 - 780 = **432 µs** | 1212 µs |

The sync pulse (2209 µs ON + 414 µs OFF) is wider than any data bit and serves exclusively as a frame delimiter. The receiver uses it to detect the start of a new message, not as data.

The full message frame is therefore: `[SYNC] [byte0] [byte1] ... [byteN]`, repeated 2 times.

## Generating and Transmitting the Signal

### Step 3 — Translating timings to BladeRF IQ samples

The BladeRF does not work with pulse durations directly. It transmits a continuous stream of **IQ samples**, where each sample is a `(I, Q)` pair representing the baseband signal at a given point in time:

- **I** (In-phase): real part, controls the carrier amplitude
- **Q** (Quadrature): imaginary part, controls the phase shift

For OOK, only the amplitude varies and Q stays at 0. The two possible sample values are:

| State | I | Q |
|-------|---|---|
| RF ON | 2000 | 0 |
| RF OFF | 0 | 0 |

The value 2000 is chosen to be close to the DAC maximum (2047) while leaving a small margin. The BladeRF plays these samples at a fixed **sample rate**. We choose **2 Msps** (2 000 000 samples/s), which gives a resolution of 0.5 µs per sample, fine enough to accurately represent our shortest pulse (376 µs = 752 samples).

Each timing value from the PDF converts directly to a sample count by multiplying by 2:

| Element | ON samples | OFF samples |
|---------|------------|-------------|
| Bit 1 | 376 × 2 = **752** | 836 × 2 = **1672** |
| Bit 0 | 780 × 2 = **1560** | 432 × 2 = **864** |
| Sync | 2209 × 2 = **4418** | 414 × 2 = **828** |

### Step 4 — Building the full message for `pico_rum`

With the sample counts established, the complete waveform is assembled as a flat list of `(I, Q)` pairs:

1. **Silence preamble** (10 000 × `0,0`, ~5 ms) to let the SDR stabilize before the first sync pulse
2. For each of **4 repetitions**:
   - Sync pulse: 4418 × `2000,0` then 828 × `0,0`
   - For each byte of `pico_rum` in ASCII, for each bit **MSB first**:
     - Bit 1: 752 × `2000,0` then 1672 × `0,0`
     - Bit 0: 1560 × `2000,0` then 864 × `0,0`
   - Inter-message silence: 20 000 × `0,0` (~10 ms), so the receiver can finalize the current message before the next sync pulse

The firmware requires 2 identical consecutive copies to validate a command. We send 4 repetitions because the first one or two may be partially lost while the receiver locks on to the signal. The extra copies ensure at least 2 clean messages get through.

### Step 5 — Generating the CSV file

The BladeRF CLI does not accept a live sample stream, it reads IQ samples from a pre-computed file. The format is a plain CSV where each line is one `(I, Q)` sample: either `2000, 0` (RF ON) or `0, 0` (RF OFF). The full signal from Step 4 is simply written line by line into this file.

The script [`pico_cmd.py`](pico_cmd.py) and the pre-generated [`pico_cmd.csv`](pico_cmd.csv) are the solution presented here. Other approaches are valid (e.g. driving a CC1101 directly from a microcontroller), but this is the one we used. [`pico_cmd.py`](pico_cmd.py) handles the full pipeline:

```python
def generate_message(data, reps=4):
    """Build the full sample list: preamble + reps × (sync + bytes + silence)."""

def write_csv(filename, samples):
    """Dump each (I, Q) pair on its own line."""
```

The resulting [`pico_cmd.csv`](pico_cmd.csv) has the following structure:

```
# Lines 1–10 000      silence preamble (5 ms × 2 Msps = 10 000 samples)
0, 0
...
# Lines 10 001–14 418  SYNC ON  (2209 µs × 2 = 4418 samples)
2000, 0
...
# Lines 14 419–15 246  SYNC OFF (414 µs × 2 = 828 samples)
0, 0
...
# Lines 15 247–…       data bits for 'p','i','c','o','_','r','u','m' (MSB first)
#   'p' = 0x70 = 0b01110000
#   bit7=0: 1560 × "2000,0"  +  864 × "0,0"
2000, 0
...
0, 0
...
#   bit6=1: 752 × "2000,0"  +  1672 × "0,0"
2000, 0
...
```

Each of the 4 repetitions ends with a 20 000-sample (10 ms) silence block.

### Step 6 — Transmitting with bladeRF-cli

With the CSV ready, the BladeRF CLI is called to transmit at the parameters from the PDF: **433.92 MHz**, **2 Msps**:

```python
def transmit(filename, repeats=10):
    """Call bladeRF-cli with the right frequency / sample-rate / gain."""
```

To run the full pipeline in one command:

```bash
python pico_cmd.py rum
```

Or, to transmit a previously generated CSV manually:

```bash
bladeRF-cli \
  -e "set frequency tx1 433920000" \
  -e "set samplerate tx1 2000000" \
  -e "set bandwidth tx1 1500000" \
  -e "set gain tx1 60" \
  -e "tx config file=pico_cmd.csv format=csv channel=1 repeat=2" \
  -e "tx start" \
  -e "tx wait"
```

### Flag

Once the beeper receives `pico_rum`, it displays the flag on screen:

```
ph0wn{rum_is_pirate_drink}
```
