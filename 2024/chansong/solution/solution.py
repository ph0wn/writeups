import sys
import re

def note_to_base12(note):
    note_to_num = {
        "C": "0",
        "C#": "1",
        "D": "2",
        "D#": "3",
        "E": "4",
        "F": "5",
        "F#": "6",
        "G": "7",
        "G#": "8",
        "A": "9",
        "A#": "a",
        "B": "b"
    }
    return note_to_num.get(note, None)

def split_notes(sequence):
    return re.findall(r'[A-G]#?', sequence)

def base12_to_ascii(single_char_list):
    base12_list = [''.join(single_char_list[i:i+2]) for i in range(0, len(single_char_list), 2)]
    output = ""
    
    for base12_num in base12_list:
        try:
            decimal_num = int(base12_num, 12)
            if 32 <= decimal_num <= 126:
                ascii_char = chr(decimal_num)
                output += ascii_char
            else:
                print(f"Skipping {base12_num}: decimal {decimal_num} is outside the ASCII printable range.")
        except ValueError:
            print(f"Invalid base-12 number: {base12_num}")
    
    return output

def convert_sequence_to_ascii(sequence):
    notes = split_notes(sequence)
    base12_sequence = [note_to_base12(note) for note in notes if note_to_base12(note) is not None]
    return base12_to_ascii(base12_sequence)

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Usage: python script.py 'note sequence'")
        sys.exit(1)
    
    sequence = sys.argv[1]
    result = convert_sequence_to_ascii(sequence)
    print(result)

