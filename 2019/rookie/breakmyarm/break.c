#include <stdio.h>
#include <string.h>

int main(void) {
  char line[30];
  char pass[] = "ld,sjwkq_d[ukq[^nkga[iu[]niy"; /* ph0wn{ouch_you_broke_my_arm} -4 */
  int i;
  
  printf("=== Break My ARM ===\n");
  printf("Password: ");

  if (fgets(line, sizeof(line), stdin) !=NULL) {
    line[strcspn(line, "\n")]= 0;
    for (i=0; i<strlen(line); i++) {
      line[i] = line[i] - 4;
    }
    if (strcmp(line, pass) == 0 ) {
      printf("\nCongrats! Flag is the password\n");
      return 0;
    } else {
      printf("\nNope. You need to feel the Force, young Padawan.\n");
    }
  }
  
  
  return 0;
}
