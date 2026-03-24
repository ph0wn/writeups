#set page(paper: "a4", margin: 2cm, fill: rgb("1a1a2e"))
#set text(font: "New Computer Modern", size: 11pt, fill: rgb("e0e0e0"))
#set par(justify: true)
#show heading: set text(fill: rgb("e94560"))

// ============================
// PAGE 1 - COVER
// ============================

#page()[
  #v(3cm)
  #align(center)[
    #text(size: 36pt, weight: "bold", fill: rgb("e94560"))[The Pirate Beeper]
    #v(0.3cm)
    #text(size: 18pt, fill: rgb("f5f5f5"))[R&D Documentation]
    #v(1cm)
    #image("u1114172957_A_menacing_cartoon_crocodile_pirate_examining_a_g_3d3aab69-7dc0-443c-897d-f3c4f44c1792_0.png", width: 50%)
    #v(1cm)
    #line(length: 60%, stroke: 1pt + rgb("e94560"))
    #v(0.5cm)
    #text(size: 14pt, fill: rgb("aaaaaa"))[
      *R&D Project -- Confidential document* \
      Classification: PIRATE SECRET
    ]
    #v(2cm)
    #text(size: 12pt, fill: rgb("666666"))[
      ph0wn CTF 2026
    ]
  ]
]

// ============================
// PAGE 2 - CONTEXT
// ============================

#pagebreak()

#text(size: 20pt, weight: "bold", fill: rgb("e94560"))[R&D Request: Pirate Beeper]
#v(0.5cm)
#line(length: 100%, stroke: 0.5pt + rgb("444444"))
#v(0.5cm)

== Context

Captain *Pico*, fearsome pirate of the digital seas, has submitted an R&D request to his crew for the design of a *tactical communication beeper*.

The objective: allow Pico to transmit orders remotely to his first mate, *Gerboise*, without using conventional radio channels that are easily intercepted.

== Beeper Features

The beeper has several command functions:

#table(
  columns: (auto, 1fr),
  stroke: 0.5pt + rgb("444444"),
  inset: 8pt,
  fill: (_, y) => if y == 0 { rgb("2a2a3e") } else { rgb("222233") },
  [*Command*], [*Description*],
  [`pico_attack`], [Attack order. Gerboise launches the assault on the target ship. The screen displays the naval battle scene.],
  [`pico_boom`], [Sabotage. Triggers the detonation of charges placed on the enemy vessel. The screen confirms the explosion.],
  [`pico_home`], [Reset the beeper to its initial state.],
)

#v(0.5cm)

Each command is transmitted over radio and decoded by the beeper, which displays the corresponding screen on the embedded display.

== Secret Feature

#rect(fill: rgb("2a2a3e"), inset: 12pt, radius: 4pt, stroke: 0.5pt + rgb("e94560"), width: 100%)[
  #text(fill: rgb("666666"), style: "italic")[REDACTED]
]

// ============================
// PAGE 3 - TECHNICAL SPECIFICATIONS
// ============================

#pagebreak()

#text(size: 20pt, weight: "bold", fill: rgb("e94560"))[Technical Specifications]
#v(0.5cm)
#line(length: 100%, stroke: 0.5pt + rgb("444444"))
#v(0.5cm)

== Hardware Platform

The beeper is built around the *STM32H573I-DK* board from ST Microelectronics, featuring an ARM Cortex-M33 microcontroller with TrustZone.

A *CC1101* radio module from Texas Instruments is connected via SPI to handle sub-GHz communications.

#table(
  columns: (auto, 1fr),
  stroke: 0.5pt + rgb("444444"),
  inset: 8pt,
  fill: (_, y) => if y == 0 { rgb("2a2a3e") } else { rgb("222233") },
  [*Component*], [*Details*],
  [MCU], [STM32H573IIK3Q -- Cortex-M33 \@ 250 MHz, 2 MB Flash, 640 KB RAM],
  [Display], [LCD 240x240 RGB565],
  [Radio], [CC1101 -- Sub-GHz transceiver (300--928 MHz), configured at *433.92 MHz*],
  [Interface], [SPI + GPIO (GDO0 on PG15)],
  [OS], [Zephyr RTOS v4.3],
)

== Radio Characteristics

The beeper uses *OOK* (On-Off Keying) modulation on the *433.92 MHz* frequency.

Commands are encoded using *PWM* (Pulse Width Modulation) with a fixed bit period of *1212 µs* and the following parameters:

#table(
  columns: (auto, auto, auto),
  stroke: 0.5pt + rgb("444444"),
  inset: 8pt,
  fill: (_, y) => if y == 0 { rgb("2a2a3e") } else { rgb("222233") },
  [*Pulse type*], [*Duration*], [*Meaning*],
  [Short pulse], [376 µs], [Bit 1],
  [Long pulse], [780 µs], [Bit 0],
  [Sync pulse], [2209 µs], [Message delimiter],
)

#v(0.5cm)

The CC1101 is configured in *asynchronous serial mode* (`IOCFG0 = 0x0D`), which allows receiving the raw OOK signal on the GDO0 pin. The firmware measures the width of each pulse through GPIO interrupts to decode the bits.

== Message Format

Commands are transmitted in *ASCII*. Each byte is sent *MSB first* (Most Significant Bit first), meaning bit 7 is transmitted first and bit 0 last. Each message is repeated *2 times* consecutively to ensure reception reliability. The firmware only validates a command if both copies are identical.

#v(1cm)
#align(center)[
  #rect(fill: rgb("2a2a3e"), inset: 12pt, radius: 4pt, stroke: 0.5pt + rgb("e94560"))[
    #text(fill: rgb("e94560"), weight: "bold")[⚓ End of document -- Yo Ho Ho! ⚓]
  ]
]
