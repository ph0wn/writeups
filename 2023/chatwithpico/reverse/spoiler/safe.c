#include <stdio.h>

#define LEN 9

void main() {
  char pincode[LEN] = "PICOCROCO";
  int i;
    
  printf("The code to unlock the safe is: ");
  for (i=0; i<LEN; i++) {
    printf("%i", (pincode[i] - 60) >> 2);
  }
  printf("\n");
}


  
