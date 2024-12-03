def crc8_lte(data):
    polynomial = 0x9B  # Polynome CRC-8/LTE
    crc = 0x00  # Initialisation a zero

    for byte in data:
        crc ^= byte  # XOR avec l'octet de donnees
        for _ in range(8):  # 8 iterations, une par bit
            if crc & 0x80:  # Si le bit de poids fort est 1
                crc = (crc << 1) ^ polynomial  # Decalage et XOR avec le polynÃ´me
            else:
                crc <<= 1  # Juste un decalage
            crc &= 0xFF  # Garder uniquement 8 bits

    return crc

for j in range(0, 4):
    data = list("704320")
    for i in range(0, 10):
        data[j+2] = f'{i}'
        result = crc8_lte(list(bytes.fromhex(''.join(data))))
        if result == 0xF6:
            print(f"---> Potential infrared data={''.join(data)}, crc={result}")
        else:
            print(f"data={''.join(data)} crc={result}")
