// ph0wn minitel 2 binary
// compile with :
// arm-linux-gnueabihf-gcc -O0 -no-pie secret.c -o secret

#include <stdio.h>
#include <string.h>
#include <stdlib.h>



    static char info[32] = "Mot de passe juste a cote ... ";
    static char password[14] = "PirateDancer";

void password2(char* ptr_buffer, char* ptr_inft,int a,int b,int c,int d,int e,int f,int g) {

    char buffer[40];

    strncpy(buffer, ptr_buffer, 40);

    if (strcmp(password, buffer) == 0) {
	printf("GOOD");
	exit(0);
    } else {
    	printf(buffer);
	exit(42);
    }
}

void password1(char* ptr_buffer, char* ptr_info,int a,int b,int c,int d,int e,int f,int g,char * flag) {

    password2(ptr_buffer,ptr_info,a,b,c,d,e,f,g);
}

int main(int argc, char** argv) {


    if (argc != 2) {
	printf("null");
      return -1;
    }

    password1(argv[1],info,1,2,3,4,5,6,7,password);

    return 0;
}
