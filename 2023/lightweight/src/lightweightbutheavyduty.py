"""
Lightweight, but heavy duty: simply relying on PRESENT with 80-bit keys.

Cooked with love by cryptopathe on Nov. 3rd, 2023.
"""


FLAG = "ph0wn{!!n0t-l1ghtweight-crypt0-5killz!!}"

# This value is formed by the 10 first bytes of the machine code of the main() symbol in the compiled executable. Cheap
# anti-debug trick if a breakpoint is set inside.

# Linux x86-64
# KEY = 0x69FF694C000041574156

# Linux armv7
KEY = 0x003180E008219FE50333

assert len(FLAG) == 40

SBOX = (0xC, 0x5, 0x6, 0xB, 0x9, 0x0, 0xA, 0xD, 0x3, 0xE, 0xF, 0x8, 0x4, 0x7, 0x1, 0x2)

INV_SBOX = (
    0x5,
    0xE,
    0xF,
    0x8,
    0xC,
    0x1,
    0x2,
    0xD,
    0xB,
    0x4,
    0x6,
    0x3,
    0x0,
    0x7,
    0x9,
    0xA,
)

P_LAYER = [
    0,
    16,
    32,
    48,
    1,
    17,
    33,
    49,
    2,
    18,
    34,
    50,
    3,
    19,
    35,
    51,
    4,
    20,
    36,
    52,
    5,
    21,
    37,
    53,
    6,
    22,
    38,
    54,
    7,
    23,
    39,
    55,
    8,
    24,
    40,
    56,
    9,
    25,
    41,
    57,
    10,
    26,
    42,
    58,
    11,
    27,
    43,
    59,
    12,
    28,
    44,
    60,
    13,
    29,
    45,
    61,
    14,
    30,
    46,
    62,
    15,
    31,
    47,
    63,
]

INVERSE_P_LAYER = [0] * 64
for i in range(64):
    INVERSE_P_LAYER[P_LAYER[i]] = i

BLOCK_SIZE = 64
ROUND_NUMBER = 32

TEST_VECTORS_80 = {
    1: (0x00000000000000000000, 0x0000000000000000, 0x5579C1387B228445),
    2: (0xFFFFFFFFFFFFFFFFFFFF, 0x0000000000000000, 0xE72C46C0F5945049),
    3: (0x00000000000000000000, 0xFFFFFFFFFFFFFFFF, 0xA112FFC72F68417B),
    4: (0xFFFFFFFFFFFFFFFFFFFF, 0xFFFFFFFFFFFFFFFF, 0x3333DCD3213210D2),
}


def apply_round_function_on(state: int, subkey: int) -> int:
    new_state = state ^ subkey

    state_as_nibbles = []

    for offset in range(0, BLOCK_SIZE, 4):
        nibble = (new_state >> (60 - offset)) & 0xF
        nibble_after_sbox = SBOX[nibble]
        state_as_nibbles.append(nibble_after_sbox)

    state_as_bits = []
    for nibble in state_as_nibbles:
        nibble_as_bits = [int(bit) for bit in f"{nibble:04b}"]
        state_as_bits.extend(nibble_as_bits)

    state_after_p_layer_as_bits = [0] * BLOCK_SIZE
    for index, bit in enumerate(state_as_bits):
        state_after_p_layer_as_bits[63 - P_LAYER[63 - index]] = bit

    round_output = 0
    for index, bit in enumerate(state_after_p_layer_as_bits):
        round_output ^= bit << (63 - index)

    return round_output


def apply_inverse_round_function_on(state: int, subkey: int) -> int:
    unkeyed_state = state ^ subkey

    state_as_bits = [int(bit) for bit in f"{unkeyed_state:064b}"]

    state_after_inverse_p_layer_as_bits = [0] * BLOCK_SIZE
    for index, bit in enumerate(state_as_bits):
        state_after_inverse_p_layer_as_bits[63 - INVERSE_P_LAYER[63 - index]] = bit

    state_after_inverse_sbox_as_nibbles = []
    for offset in range(0, BLOCK_SIZE, 4):
        nibble = int(
            "".join(
                [
                    str(bit)
                    for bit in state_after_inverse_p_layer_as_bits[offset : offset + 4]
                ]
            ),
            2,
        )
        state_after_inverse_sbox_as_nibbles.append(INV_SBOX[nibble])

    state_after_inverse_sbox_as_int = 0
    for index, nibble in enumerate(state_after_inverse_sbox_as_nibbles):
        state_after_inverse_sbox_as_int ^= nibble << (60 - 4 * index)

    return state_after_inverse_sbox_as_int


def update_key_register_from(key_register: int, round_number: int) -> int:
    key_register_as_bits = [int(bit) for bit in f"{key_register:080b}"]

    rotated_key_register_as_bits = (
        key_register_as_bits[-19:] + key_register_as_bits[:-19]
    )

    rotated_key_register_as_int = 0
    for index, bit in enumerate(rotated_key_register_as_bits):
        rotated_key_register_as_int += bit << (79 - index)

    upper_nibble_as_int = rotated_key_register_as_int >> 76

    upper_nibble_after_sbox_as_int = SBOX[upper_nibble_as_int]

    xored_part_as_int = ((rotated_key_register_as_int >> 15) & 0x1F) ^ round_number

    next_key_register_as_int = (
        (rotated_key_register_as_int & 0x0FFFFFFFFFFFFFF07FFF)
        ^ (upper_nibble_after_sbox_as_int << 76)
        ^ (xored_part_as_int << 15)
    )

    return next_key_register_as_int


def encrypt(plaintext: int, key: int) -> int:
    key_schedule = []

    key_register = key
    round_state = plaintext

    # At first, we compute the key-schedule
    for round in range(ROUND_NUMBER):
        key_schedule.append(key_register >> 16)
        key_register = update_key_register_from(key_register, round + 1)

    # Then, we compute the actual encryption
    for round in range(ROUND_NUMBER - 1):
        round_state = apply_round_function_on(round_state, key_schedule[round])

    # Eventually, we apply the last subkey
    round_state ^= key_schedule[-1]

    return round_state


def decrypt(ciphertext: int, key: int) -> int:
    key_schedule = []

    key_register = key
    round_state = ciphertext

    # At first, we compute the key-schedule
    for round in range(ROUND_NUMBER):
        key_schedule.append(key_register >> 16)
        key_register = update_key_register_from(key_register, round + 1)

    # Then, we compute the actual decryption
    for round in range(ROUND_NUMBER - 1, 0, -1):
        round_state = apply_inverse_round_function_on(round_state, key_schedule[round])

    round_state ^= key_schedule[0]

    return round_state


if __name__ == "__main__":
    print("Testing PRESENT with 80-bit keys:")
    for test_case in TEST_VECTORS_80:
        key = TEST_VECTORS_80[test_case][0]
        plaintext = TEST_VECTORS_80[test_case][1]

        ciphertext = encrypt(plaintext, key)

        if ciphertext == TEST_VECTORS_80[test_case][2]:
            print("Success", hex(ciphertext))
        else:
            print("Failure", hex(ciphertext))

        plaintext = decrypt(ciphertext, key)

        if plaintext == TEST_VECTORS_80[test_case][1]:
            print("Success", hex(plaintext))
        else:
            print("Failure", hex(plaintext))

    print("")

    print("Dumping expected ciphertexts")

    flag_as_ints = [int(c) for c in FLAG.encode()]

    PLAINTEXT1 = 0
    for b in flag_as_ints[:8]:
        PLAINTEXT1 = (PLAINTEXT1 << 8) + b

    PLAINTEXT2 = 0
    for b in flag_as_ints[8:16]:
        PLAINTEXT2 = (PLAINTEXT2 << 8) + b

    PLAINTEXT3 = 0
    for b in flag_as_ints[16:24]:
        PLAINTEXT3 = (PLAINTEXT3 << 8) + b

    PLAINTEXT4 = 0
    for b in flag_as_ints[24:32]:
        PLAINTEXT4 = (PLAINTEXT4 << 8) + b

    PLAINTEXT5 = 0
    for b in flag_as_ints[32:]:
        PLAINTEXT5 = (PLAINTEXT5 << 8) + b

    ciphertext1 = encrypt(PLAINTEXT1, KEY)
    ciphertext2 = encrypt(PLAINTEXT2, KEY)
    ciphertext3 = encrypt(PLAINTEXT3, KEY)
    ciphertext4 = encrypt(PLAINTEXT4, KEY)
    ciphertext5 = encrypt(PLAINTEXT5, KEY)

    print(
        f'Ciphertext 1: {", ".join([hex((ciphertext1 >> 8*(7-offset) & 0xFF)) for offset in range(8)])}'
    )
    print(
        f'Ciphertext 2: {", ".join([hex((ciphertext2 >> 8*(7-offset) & 0xFF)) for offset in range(8)])}'
    )
    print(
        f'Ciphertext 3: {", ".join([hex((ciphertext3 >> 8*(7-offset) & 0xFF)) for offset in range(8)])}'
    )
    print(
        f'Ciphertext 4: {", ".join([hex((ciphertext4 >> 8*(7-offset) & 0xFF)) for offset in range(8)])}'
    )
    print(
        f'Ciphertext 5: {", ".join([hex((ciphertext5 >> 8*(7-offset) & 0xFF)) for offset in range(8)])}'
    )
