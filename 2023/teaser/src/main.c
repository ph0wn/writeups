#include <stdio.h>
#include <stdlib.h>

char *deobfuscate( char *input, int len,  char key) {
  char *ret = malloc(sizeof( char)*(len+1));
  ret[len] = 0x00;
  for (int i = 0; i< len; i++) {
    ret[i] = input[i] ^ key;
  }
  return ret;
}

void main() {
  // ----- PDU---1/1 ------\n001100098121436587F900000B3CC176589F769F43A0ECBB0E9A87EFA0343D046296E9A0FA1CB476BFEFA186028E86DDDD1BD4BDDC92B6EEE1343DED3EB768ADEA2605\nLength: 66
  char msg[] = { 0xe, 0xe, 0xe, 0xe, 0xe, 0x3, 0x73, 0x67, 0x76, 0xe, 0xe, 0xe, 0x12, 0xc, 0x12, 0x3, 0xe, 0xe, 0xe, 0xe, 0xe, 0xe, 0x29, 0x13, 0x13, 0x12, 0x12, 0x13, 0x13, 0x13, 0x1a, 0x1b, 0x12, 0x11, 0x12, 0x17, 0x10, 0x15, 0x16, 0x1b, 0x14, 0x65, 0x1a, 0x13, 0x13, 0x13, 0x13, 0x13, 0x61, 0x10, 0x60, 0x60, 0x12, 0x14, 0x15, 0x16, 0x1b, 0x1a, 0x65, 0x14, 0x15, 0x1a, 0x65, 0x17, 0x10, 0x62, 0x13, 0x66, 0x60, 0x61, 0x61, 0x13, 0x66, 0x1a, 0x62, 0x1b, 0x14, 0x66, 0x65, 0x62, 0x13, 0x10, 0x17, 0x10, 0x67, 0x13, 0x17, 0x15, 0x11, 0x1a, 0x15, 0x66, 0x1a, 0x62, 0x13, 0x65, 0x62, 0x12, 0x60, 0x61, 0x17, 0x14, 0x15, 0x61, 0x65, 0x66, 0x65, 0x62, 0x12, 0x1b, 0x15, 0x13, 0x11, 0x1b, 0x66, 0x1b, 0x15, 0x67, 0x67, 0x67, 0x67, 0x12, 0x61, 0x67, 0x17, 0x61, 0x67, 0x67, 0x60, 0x1a, 0x11, 0x61, 0x15, 0x66, 0x66, 0x66, 0x12, 0x10, 0x17, 0x10, 0x67, 0x66, 0x67, 0x10, 0x66, 0x61, 0x14, 0x15, 0x1b, 0x62, 0x67, 0x66, 0x62, 0x11, 0x15, 0x13, 0x16, 0x29, 0x6f, 0x46, 0x4d, 0x44, 0x57, 0x4b, 0x19, 0x3, 0x15, 0x15 };
  char *toprint = deobfuscate(msg, sizeof(msg), 0x23);
  printf("%s", toprint);
  free(toprint);
}
