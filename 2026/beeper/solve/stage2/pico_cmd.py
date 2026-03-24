#!/usr/bin/env python3
"""
Pico Command Sender for BladeRF
Send screen commands via OOK PWM at 433.92 MHz
"""

import struct
import argparse
import subprocess
import os

SAMPLE_RATE = 2000000

# PWM timings (microseconds)
PWM_SHORT_US = 376
PWM_LONG_US = 780
PWM_SYNC_US = 2209
PWM_SHORT_GAP_US = 836
PWM_LONG_GAP_US = 432
PWM_SYNC_GAP_US = 414

AMPLITUDE_ON = 2000
AMPLITUDE_OFF = 0

COMMANDS = {
    'attack': 'pico_attack',
    'rum':    'pico_rum',
    'boom':   'pico_boom',
    'home':   'pico_home',
}

def us_to_samples(us):
    return int(us * SAMPLE_RATE / 1000000)

def generate_samples(on_us, off_us):
    samples = []
    for _ in range(us_to_samples(on_us)):
        samples.append((AMPLITUDE_ON, 0))
    for _ in range(us_to_samples(off_us)):
        samples.append((AMPLITUDE_OFF, 0))
    return samples

def generate_sync():
    return generate_samples(PWM_SYNC_US, PWM_SYNC_GAP_US)

def generate_bit(bit):
    if bit:
        return generate_samples(PWM_SHORT_US, PWM_SHORT_GAP_US)
    else:
        return generate_samples(PWM_LONG_US, PWM_LONG_GAP_US)

def generate_byte(byte):
    samples = []
    for i in range(7, -1, -1):
        samples.extend(generate_bit((byte >> i) & 1))
    return samples

def generate_message(data, reps=4):
    samples = []
    for _ in range(us_to_samples(5000)):
        samples.append((AMPLITUDE_OFF, 0))

    for _ in range(reps):
        samples.extend(generate_sync())
        for byte in data:
            samples.extend(generate_byte(byte))
        for _ in range(us_to_samples(10000)):
            samples.append((AMPLITUDE_OFF, 0))

    return samples

def write_csv(filename, samples):
    with open(filename, 'w') as f:
        for i, q in samples:
            f.write(f"{i}, {q}\n")
    return len(samples)

def transmit(filename, repeats=10):
    """Transmit via BladeRF"""
    cmd = [
        'bladeRF-cli',
        '-e', 'set frequency tx1 433920000',
        '-e', 'set samplerate tx1 2000000',
        '-e', 'set bandwidth tx1 1500000',
        '-e', 'set gain tx1 60',
        '-e', f'tx config file={filename} format=csv channel=1 repeat={repeats}',
        '-e', 'tx start',
        '-e', 'tx wait',
    ]
    subprocess.run(cmd)

def main():
    parser = argparse.ArgumentParser(description='Pico Command Sender')
    parser.add_argument('command', nargs='?', choices=list(COMMANDS.keys()),
                        help='Command to send')
    parser.add_argument('-m', '--message', help='Custom message')
    parser.add_argument('-r', '--reps', type=int, default=4, help='Repetitions in file')
    parser.add_argument('-t', '--tx-reps', type=int, default=2, help='TX repetitions')
    parser.add_argument('--no-tx', action='store_true', help='Generate only, no TX')
    parser.add_argument('-l', '--list', action='store_true', help='List commands')
    args = parser.parse_args()

    if args.list:
        print("Available commands:")
        for short, full in COMMANDS.items():
            print(f"  {short:10} -> {full}")
        return

    if args.message:
        msg = args.message
    elif args.command:
        msg = COMMANDS[args.command]
    else:
        parser.print_help()
        print("\nExamples:")
        print("  python pico_cmd.py attack      # Send pico_attack")
        print("  python pico_cmd.py rum         # Send pico_rum")
        print("  python pico_cmd.py -m 'Hello'  # Send custom message")
        return

    data = msg.encode('ascii')
    print(f"Command: {msg}")
    print(f"Hex: {data.hex()}")

    # Generate and save
    script_dir = os.path.dirname(os.path.abspath(__file__))
    filename = os.path.join(script_dir, 'pico_cmd.csv')

    samples = generate_message(data, args.reps)
    write_csv(filename, samples)
    print(f"Generated: {filename} ({len(samples)} samples)")

    if not args.no_tx:
        print(f"Transmitting ({args.tx_reps} repeats)...")
        transmit(filename, args.tx_reps)
        print("Done!")
    else:
        print("--no-tx: Skipping transmission")

if __name__ == '__main__':
    main()
