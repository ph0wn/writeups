# Pico Beeper

A 2-stage RF challenge for ph0wn CTF 2026.

## Overview

| | |
|---|---|
| **Hardware** | STM32H573I-DK + CC1101 |
| **Frequency** | 433.92 MHz |
| **Modulation** | OOK/ASK with PWM encoding |
| **Category** | Hardware / RF / Reverse Engineering |

## Challenge Stages

| Stage | Points | Description |
|-------|--------|-------------|
| **Stage 1** | 50 | Reverse the firmware to find a hidden command |
| **Stage 2** | 400 | Transmit the hidden command via RF to trigger a secret feature |

## Directory Structure

```
beeper/
├── README.md
├── build.sh                    # Build script (doc + firmware + dist)
├── description.md              # Stage 1 challenge description
├── description2.md             # Stage 2 challenge description
├── src/
│   ├── doc/                    # R&D documentation (typst)
│   ├── stage1/                 # Firmware stage 1 (minimal display)
│   └── stage2/                 # Firmware stage 2 (full pirate graphics)
└── solution/
    ├── stage1/                 # Stage 1 writeup
    └── stage2/                 # Stage 2 writeup + TX script
```

## Building the Challenge

Requirements:
- [Zephyr SDK](https://docs.zephyrproject.org/latest/develop/getting_started/index.html)
- Typst (for documentation) (ex: `cargo install --locked typst-cli`)

```bash
source ~/zephyrproject/.venv/bin/activate
```

### Quick build (recommended)

The beeper dir is an "application" of Zephyr. So, copy it in the dir where you installed Zephyr: `cp -R beeper ~/softs/zephyr`, and run the build from there.


`build.sh` compiles the documentation, builds the Stage 1 firmware, and populates `dist/` with the challenge files:

```bash
./build.sh
```

This produces:
- `dist/stage1/` — stripped `firmware.elf` + `doc.pdf`
- `dist/stage2/` — `doc.pdf`

### Manual build

#### Generate documentation

```bash
cd src/doc
typst compile doc.typ
```

#### Build Stage 1 firmware

```bash
cd src/stage1
west build -b stm32h573i_dk
```

#### Build Stage 2 firmware + flash

```bash
cd src/stage2
west build -b stm32h573i_dk
west flash
```

## Author

- Alexis BARET (gerboise)
