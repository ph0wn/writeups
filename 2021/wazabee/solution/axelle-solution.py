import logging

logging.basicConfig(format='%(levelname)s:%(message)s', level=logging.INFO)

# from https://www.sstic.org/media/SSTIC2020/SSTIC-actes/wazabee_attaque_de_reseaux_zigbee_par_detournement/SSTIC2020-Article-wazabee_attaque_de_reseaux_zigbee_par_detournement_de_puces_bluetooth_low_energy-galtier_marconat_AhcLCGU.pdf
pn = {}
pn['0000'] = '1100000011101111010111001101100'
pn['1000'] = '1001110000001110111101011100110'
pn['0100'] = '0101100111000000111011110101110'
pn['1100'] = '0100110110011100000011101111010'
pn['0010'] = '1101110011011001110000001110111'
pn['1010'] = '0111010111001101100111000000111'
pn['0110'] = '1110111101011100110110011100000'
pn['1110'] = '0000111011110101110011011001110'
pn['0001'] = '0011111100010000101000110010011'
pn['1001'] = '0110001111110001000010100011001'
pn['0101'] = '1010011000111111000100001010001'
pn['1101'] = '1011001001100011111100010000101'
pn['0011'] = '0010001100100110001111110001000'
pn['1011'] = '1000101000110010011000111111000'
pn['0111'] = '0001000010100011001001100011111'
pn['1111'] = '1111000100001010001100100110001'

def get_block(input : str) -> str:
    for b in pn.keys():
        if pn[b].startswith(input):
            logging.debug("found b={} for input={}".format(b, input))
            return b

    assert( False ), "Could not find any corresponding block for input={}".format(input)

def bit_swap(abyte : bytes) -> int:
    return int('{:08b}'.format(abyte)[::-1], 2)

def decode_pn(sequence: str) -> [str]:
    result = []
    sequence_bytes = bytes.fromhex(sequence)
    i = 0
    while (i + 3) < len(sequence_bytes):
        # bit swap
        b0 = bit_swap(sequence_bytes[i])
        b1 = bit_swap(sequence_bytes[i+1])
        b2 = bit_swap(sequence_bytes[i+2])

        # get the corresponding block
        pn = '{:08b}{:08b}{:08b}'.format(b0, b1, b2)
        result.append(get_block(pn))
        
        # skip 4th byte - we have enough info to get the corresponding block
        i = i + 4

    return result

def bits_to_hexstring(thearray: [str]) -> str:
    # [ '1101', '1111' ] -> 0xef...
    return hex(int(''.join(thearray), 2))

def bit_swap_to_string(hexstring: str) -> str:
    '''
    input: 0xe5280e160cee...
    output: string
    We take each byte (e.g e5) and bit swap its bits (a7)
    Then, we convert that to characters...
    '''
    listofbytes = list(bytes.fromhex(hexstring.replace('0x','')))
    result = ''
    for l in listofbytes:
        result = result + chr(bit_swap(l))
    return result
    
# from SecondFragment code
pnseq = '70afb33965fc08453b9b03773970af3303f73a1b70afb339fc08c564f73a9b0303f73a1bb239702f70afb33970afb33908c5647cf73a9b034dc68f5070afb339c68f504cb239702f514cc60fb239702f4dc68f50f73a9b0303f73a1b70afb339c464fc08aeb33970b239702ff73a9b03f73a9b0370afb33965fc0845aeb33970c464fc0870afb33970afb33970afb339aeb33970aeb33970514cc60f70afb33903f73a1bc68f504c70afb3393970af33'

if __name__ == '__main__':
    corresponding_blocks = decode_pn(pnseq)
    logging.debug(corresponding_blocks)

    hexstring = bits_to_hexstring(corresponding_blocks)
    logging.debug(hexstring)

    solution = bit_swap_to_string(hexstring)
    logging.info("Message: {}".format(solution))

