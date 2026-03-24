import serial
import time

ships = [3, 3, 2, 2, 1]
GRID_SIZE = 24

PORT = "/dev/ttyACM5"
BAUD = 115200
TIMEOUT = 0.5

def rand(m):
    global prng_state
    prng_state ^= (prng_state << 13) & 0xffffffff
    prng_state ^= (prng_state >> 17) & 0xffffffff
    prng_state ^= (prng_state << 5)  & 0xffffffff
    return prng_state % m

def check_collision(x: int, y: int, length: int, horiz: bool) -> bool:
    global grid
    mask = 0x01 | 0x02
    for i in range(length):
        cx = x + i if horiz else x
        cy = y if horiz else y + i
        if grid[cx][cy] & mask:
            return True
    return False

def wait_for_data(ser, max_wait=None, poll_delay=0.01):
    start = time.monotonic()
    while ser.in_waiting == 0:
        if max_wait is not None and (time.monotonic() - start) >= max_wait:
            raise TimeoutError("No data received from serial within max_wait.")
        time.sleep(poll_delay)

def read_prng_state(ser):
    prefix = ser.read_until(b"(")
    raw = ser.read_until(b")")
    hex_bytes = raw[:-1].strip()
    if hex_bytes.startswith(b"0x") or hex_bytes.startswith(b"0X"):
        hex_bytes = hex_bytes[2:]
    state = int(hex_bytes, 16)
    return prefix, state


grid = [[0 for _ in range(GRID_SIZE)] for _ in range(GRID_SIZE)]

if __name__ == "__main__":
    while True:
        try:
            ser = serial.Serial(PORT, BAUD, timeout=TIMEOUT)
            break
        except serial.SerialException:
            time.sleep(0.2)

    while True:
        if ser.in_waiting > 0:
            first_byte = ser.read(1)
            print("First byte received:", first_byte)
            break

    print("LEVEL 1 - START")
    prefix, prng_state = read_prng_state(ser)
    print(prefix)
    print("prng_state:", hex(prng_state))

    for s in range(len(ships)):
        placed = False
        while not placed:
            h = rand(2)
            y = rand(GRID_SIZE)
            x = rand(GRID_SIZE)
            l = ships[s]

            if h == 1 and (x + l > GRID_SIZE):
                x = GRID_SIZE - l
            if h == 0 and (y + l > GRID_SIZE):
                y = GRID_SIZE - l

            if not check_collision(x, y, l, h):
                if h == 1:
                    for i in range(l):
                        grid[x+i][y] = 1
                        ser.write(f"fire {x+i} {y}\n".encode())
                        print(ser.readlines())
                        time.sleep(0.5)
                else:
                    for i in range(l):
                        grid[x][y+i] = 1
                        ser.write(f"fire {x} {y+i}\n".encode())
                        print(ser.readlines())
                        time.sleep(0.5)
                print(hex(prng_state))
                print(f"ship[{s}] y={y} x={x} h={h}")
                placed = True

    print("LEVEL 1 - END")
    time.sleep(2)
    print("LEVEL 2 - START")
    prng_ghost = [0x464F4C4C, 0x4F575F54, 0x48455F47, 0x484F5354]

    for d in range(4):
        prng_state ^= prng_ghost[d]
        placed = False
        while not placed:
            h = rand(2)
            y = rand(GRID_SIZE//2)
            x = rand(GRID_SIZE//4)
            print(f"ghost[{d}] y={y} x={x} h={h}")
            if not check_collision(x, y, 1, h):
                grid[x][y] = 2
                ser.write(f"fire {x} {y}\n".encode())
                print(ser.readlines())
                placed = True
        time.sleep(0.5)

    print(ser.read_all())
    ser.write(f"map\n".encode())
    print(ser.readall().decode())
    print("LEVEL 2 - END")