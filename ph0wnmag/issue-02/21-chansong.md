# Misc challenges at Ph0wn 2024

## Chansong by Bastien

### Description

Ph0wn is great. And it’s so great that this year, a CD was released in its honor. Maybe its songs are hiding something... So listen closely. But remember: sometimes, the truth is hidden behind the words...

### Overall idea

The flag is encoded using a base-12-like system and is hidden within the metadata of one of the songs. To retrieve it, it is necessary to:
1. Extract the encoded sequence from the metadata.
2. Analyze the encoding scheme, which is explained in another song.
3. Develop a script to decode the sequence and reveal the flag.

### Retrieving the sequence

The sequence is hidden within the metadata of the Ph0wn anthem.

```
$ exiftool anthem.mp3
```
outputs the following information:
```
[...]
Comment: AE G#G# EC AB AD A#D# AB ED# AC AC G#E EC AD ED# EC AC G#E G#D# G#G# EE AE A#F
[...]
```

### Analyzing the encoding scheme

The lyrics of **ListenMe.mp3** provide an explanation of the encoding scheme:
```
Listen to my isomorphic song
Which maps the pitch classes to a set of symbols
The pitch classes are C, C sharp, and span up to B
And symbols span from 0 to 9, plus an A and a B

It maps C to 0, C sharp to 1, you see?
D is mapped to 2, D sharp is mapped to three
That continues until the mapping is complete
A sharp to symbol A, and B to symbol B
```

From these lyrics, we deduce that the sequence is a base-12 code with an additional transformation that maps the set {0, 1, ..., 9, a, b} to the set of pitch classes {C, C#, D, ..., B}. This transformation is a bijection between the two sets, defined as follows: {(0,C), (1,C#), (2,D), ..., (a,A#), (b,B)}.

This mapping forms the basis for decoding the sequence.


### Decoding the sequence

The idea here is to:
1. Convert the pitch-class sequence into a sequence of base-12 numbers.
2. Transform the resulting base-12 sequence into a sequence of decimal numbers.
3. Convert the decimal sequence into an alphanumeric string using ASCII code to reveal the flag.

The following Python script accomplishes this:

```
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
```
