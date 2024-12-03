#include <stdio.h>
#include <string.h> /* for memset */
#include "pico/stdlib.h"

char lights = 0x00;
char motor = 0x00;

#define PASSWORD_LEN 21
#define KEY 0x45

int menu() {
  char choice;

  printf("\n      ______     +------------+\n");
  printf("   __//_||_\\__   |  Pico      |\n");
  printf("  |     ||    |__| Car Status |\n");
  printf("  '--(_)--(_)-'  +------------+\n\n");
  printf("Lights: %s", lights == 0x00 ? "OFF" : "ON");
  printf(" Motor: %s", motor == 0x00 ? "OFF" : "ON");
  printf("\n-----------------------\n");
  printf(" 1. %s\n", lights == 0x00 ? "Turn lights ON" : "Turn lights OFF");
  printf(" 2. %s\n", motor == 0x00 ? "Start engine" : "Read Flag" );
  printf("\nEnter your choice: ");
  choice = getchar();
  printf("%c\n", choice); /* echo choice */
  return choice;
}

int read_password(char *password, int len){
  memset(password, 0x41, len);
  printf("\n=== H1dden Pic0 Menu ===\n");
  printf("Password (* to END): ");

  for (int i=0;i<len; i++) {
    password[i] = getchar();
    if (password[i] == '*') {
      password[i] = '\0';
      break;
    }
    // echo the character
    printf("%c", password[i]);
  }
  // user didn't finish by * or ENTER
  if (password[len - 1] != '\0')
    return PASSWORD_LEN + 10;
  else 
    return strlen(password);
}

void xor(char *ciphertext, char *plaintext, const char key, int len) {
  for (int i = 0; i < len; i++) {
    ciphertext[i] = plaintext[i] ^ key;
  }
}


int main() {
  char userInput;
  char userpass[PASSWORD_LEN+1];
  stdio_init_all();

  while (!stdio_usb_connected()) {
    sleep_ms(100);
  }
   
  while (1) {
    char choice;
    
    choice = menu();
    switch(choice) {
    case '1':
      lights = (lights == 0x00) ? 1 : 0;
      break;
    case '2':
      if (motor == 0x01) {
	printf("Congrats! Flag is ph0wn{%s}\n", userpass);
	motor = 0x00;
	memset(userpass, 0x00, PASSWORD_LEN+1);
      } else {
	printf("Ouch! The engine stalled!!!\n");
      }
      break;
    case '3':
      {
	char ciphertext[PASSWORD_LEN+1];
	char encrypted_flag [] = {0x33, 0x37, 0x2a, 0x30, 0x28, 0x1a, 0x26, 0x37, 0x2a, 0x26, 0x2a, 0x27, 0x20, 0x24, 0x31, 0x36, 0x28, 0x24, 0x37, 0x2c, 0x2a};
	int password_len = read_password(userpass, PASSWORD_LEN+1);
	xor(ciphertext, userpass, KEY, PASSWORD_LEN+1);
	if (password_len == PASSWORD_LEN &&
	    memcmp(ciphertext, encrypted_flag, (unsigned int)PASSWORD_LEN) == 0) {
	  printf("\nVROOOOOOOOOOOOOM! You started the engine!\n");
	  motor = 0x01;
	} else {
	  printf("\nAccess denied!\n");
	}
	break;
      }
      // we are not doing anything for the other inputs
    }
    // we dont need to sleep as menu will block until answered
  }
  

  return 0;
}
