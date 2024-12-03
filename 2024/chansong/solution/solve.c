#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <math.h>
#include <assert.h>

const char* notes[] = { "C", "C#", "D", "D#", "E", "F", "F#", "G", "G#", "A", "A#", "B" };
const char msg[] = "AE G#G# EC AB AD A#D# AB ED# AC AC G#E EC AD ED# EC AC G#E G#D# G#G# EE AE A#F";

// It maps C to 0, C sharp to 1, you see?
// D is mapped to 2, D sharp is mapped to three
void transform(const char* msg, int len, char* result) {
    int pos = 0;
    assert(result != NULL);
    assert(msg != NULL);

    for (int i = 0; i < len; i++) {
      if (msg[i] == 'B' || msg[i] == ' ') {
	result[pos++] = msg[i];
      } else if (msg[i] == '#') {
	// already processed
	continue;
      } else {
	// is the next character sharp?
	int sharp = 0;
	if (i + 1 < len && msg[i + 1] == '#') {
	  sharp = 1;
	}

	// find the index of the current character in the notes array
	int idx = -1;
	for (int j = 0; j < 12; j++) {
	  if (msg[i] == notes[j][0] && (notes[j][1] == '\0' || (sharp && notes[j][1] == '#'))) {
	    idx = j;
	    break;
	  }
	}

	if (sharp) {
	  idx++;
	  i++; // skip the sharp
	}

	// write in base 12
	if (idx < 10) {
	  result[pos++] = '0' + idx;
	} else {
	  result[pos++] = 'A';
	}
      }
    }
    result[pos] = '\0';
}

// convert from base 12 to base 10
int base12_to_base10(const char* base12_str, int len) {
    int base10_value = 0;

    for (int i = 0; i < len; i++) {
        int digit;
        if (base12_str[i] >= '0' && base12_str[i] <= '9') {
            digit = base12_str[i] - '0';
        } else if (base12_str[i] == 'A') {
            digit = 10;
        } else if (base12_str[i] == 'B') {
            digit = 11;
        } else {
	  perror("Invalid base12 character");
	  exit(2);
        }
        base10_value = base10_value * 12 + digit;
    }

    return base10_value;
}

int main() {
    char transformed[256];
    transform(msg, strlen(msg), transformed);

    // split the base12 string for each space
    char* token = strtok(transformed, " ");
    char flag[256];
    int flag_pos = 0;

    // convert each base10 pair to character
    while (token != NULL) {
      int num = base12_to_base10(token, strlen(token));
      flag[flag_pos++] = (char)num;
      token = strtok(NULL, " ");
    }
    flag[flag_pos] = '\0';

    printf("Flag: %s\n", flag);
    return 0;
}
