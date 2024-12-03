import struct

# Constants for UF2 format
UF2_BLOCK_SIZE = 512
UF2_FLAG_FAMILYID_PRESENT = 0x00002000
UF2_MAGIC_START0 = 0x0A324655  # "UF2\n"
UF2_MAGIC_START1 = 0x9E5D5157  # Randomly selected
UF2_MAGIC_END = 0x0AB16F30     # Randomly selected

def parse_uf2(file_path):
    with open(file_path, 'rb') as file:
        blocks = []
        
        while True:
            block = file.read(UF2_BLOCK_SIZE)
            if len(block) < UF2_BLOCK_SIZE:
                break
            blocks.append(block)
        
        return blocks

def extract_data(blocks):
    data = bytearray()
    
    for block in blocks:
        # Unpack the header of the UF2 block
        header = struct.unpack_from('<IIIIIIIIIIII', block, 0)
        magic_start0, magic_start1, flags, target_addr, payload_size, block_no, num_blocks, file_size, family_id, _ = header[:10]

        # Validate the UF2 magic numbers
        if magic_start0 != UF2_MAGIC_START0 or magic_start1 != UF2_MAGIC_START1:
            print("Invalid UF2 magic numbers")
            continue
        
        # Extract the payload
        payload = block[32:32 + payload_size]
        data.extend(payload)
    
    return data

def save_extracted_data(data, output_file):
    with open(output_file, 'wb') as file:
        file.write(data)

# Example usage
uf2_file_path = 'firmware.uf2'
output_file_path = 'extracted_data.bin'

# Parse the UF2 file
uf2_blocks = parse_uf2(uf2_file_path)

# Extract the data
extracted_data = extract_data(uf2_blocks)

# Save the extracted data to a binary file
save_extracted_data(extracted_data, output_file_path)

print(f"Extracted data saved to {output_file_path}")
