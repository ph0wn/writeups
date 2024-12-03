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

# Paquets de donnees sous forme d'octets
data1    = "336863" # N 43 36.863
data2    = "704420"  # E 007 04.420
data3    = "704320"

# Calcul des CRC
crc1 = crc8_lte(list(bytes.fromhex(data1)))
crc2 = crc8_lte(list(bytes.fromhex(data2)))
crc3 = crc8_lte(list(bytes.fromhex(data3)))

print(list(bytes.fromhex(data1)))
print(f'data={data1} crc={crc1:X}')
print(f'data={data2} crc={crc2:X}')
print(f'data={data3} crc={crc3:X}')
