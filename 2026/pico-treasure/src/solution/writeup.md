# Pico Treasure — Write-Up

## 0. What we are given
We are presented with a physical safe equipped with a keypad and a short description hinting that the key is *in the air* and that we must *listen*. There is no file provided, no QR code, and no obvious digital artifact to start from.

---

## 1. Discovering the network
We begin by scanning nearby wireless networks and notice a Wi-Fi network named:

```
pico_treasure
```

We connect to this network. There is no captive portal, no website, and no visible services responding to standard requests. This suggests the network is not meant for browsing, but for passive observation.

---

## 2. Listening to the air
Taking the hint literally, we start monitoring network traffic.

Using tcpdump we observe a continuous stream of UDP packets on the network. Filtering traffic on a specific port on which there seems to be constant transmission, reveals repeated packets with a recognizable structure.

The payloads contain:
- a consistent textual prefix
- a session identifier
- an index in the form `i/N`
- a long Base64-looking string

This confirms that data is being broadcast, not served on request.

---

## 3. Reconstructing the transmitted artifact
From the observed packets, we infer that the broadcast consists of chunked data, sent repeatedly in cycles.

We capture the packets and:
1. Group them by session identifier
2. Extract the chunk index `i/N`
3. Sort chunks by index
4. Base64-decode each payload
5. Concatenate the decoded bytes in order

After reassembly, the result is a single file. Running `strings` on it (and optionally `file`) shows a compiled executable built from C.

---

## 4. Recovering the time dependency
We run the binary under a tracer (e.g. strace or ltrace) and trigger the prompt. We see that the program calls the system clock to get the current time. From that we infer that the 14-digit code is driven by a time value. By inspecting how that value is used (or by testing), we conclude that the program takes the Unix timestamp in seconds and divides it by 60. So the code depends on the current minute since the Unix epoch: the correct keypad sequence is a function of that minute.

---

## 5. Locating the code-generation routine
Opening the binary in Ghidra reveals a compact function operating on 64-bit integers. The function applies the following operations to the minute value:

- XOR with a 64-bit constant
- Multiplication by another 64-bit constant (wrapping arithmetic)
- A 5-bit rotate-left
- Addition of a third 64-bit constant (wrapping)
- A final modulo with 10^14

The constants extracted are:

```
K1 = 0xC0B41E7ED15EA5E1
K2 = 0xBADC0FFEE0DDF00D
K3 = 0x1EA57EADBEAD1DEA
MOD = 100000000000000
```

The function is linear and contains no branches.

---

## 6. Reconstructed generator
From the disassembly, we reconstruct the logic as:

```
x = minutes XOR K1
x = (x * K2) mod 2^64
x = ROTL(x, 5)
x = (x + K3) mod 2^64
code = x mod 10^14
```

The program compares the entered sequence against values generated for:

```
minutes - 1
minutes
minutes + 1
```

This provides a ±1 minute tolerance.

---

## 7. Re-implementing the generator

```python
import time

MOD_14 = 100_000_000_000_000

K1 = 0xC0B41E7ED15EA5E1
K2 = 0xBADC0FFEE0DDF00D
K3 = 0x1EA57EADBEAD1DEA

def generate_code(minutes: int) -> int:
    x = minutes ^ K1
    x = (x * K2) & 0xFFFFFFFFFFFFFFFF
    x = ((x << 5) | (x >> (64 - 5))) & 0xFFFFFFFFFFFFFFFF
    x = (x + K3) & 0xFFFFFFFFFFFFFFFF
    return x % MOD_14

def format_code(minutes: int) -> str:
    return f"{generate_code(minutes):014d}"

if __name__ == "__main__":
    minutes = int(time.time() // 60)
    print("Current minutes:", minutes)
    print("Code:", format_code(minutes))

```

---

## 8. Final step
We compute the code for the current minute (or adjacent minutes if needed) and manually enter it on the safe keypad, opening the safe and obtaining the flag.
