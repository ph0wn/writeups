#include "mbed.h"
#include "mbedtls/aes.h"

/* 
 * This Challenge is distributed in two versions: one for handing out to
 * participants, one for validation at the orgs
 * The PUBLIC switch changes between those versions
 */
#define PUBLIC 1
#define SC_SIZE 4096


char  __attribute__((section (".magic_easy"))) magic_easy[] = "______\0";
const char __attribute__((section (".magic"))) magic_hard[] = "______\0";


/*
 * To prevent egghunting shellcode, we keep the hard flag in memory encrypted.
 * Furthermore, the key will be in a seperate section in the validation version
 * to prevent direct memory readout.
 */
#if PUBLIC
const char flag_easy[64] = "ph0wn{get_real_flag_on_the_organizers_desk}\0";
const unsigned char flag_hard[] = {0x53, 0x99, 0x91, 0x2c, 0x30, 0xc, 0x65, 0xdc,
    0x91, 0x1d, 0x1f, 0x77, 0x6b, 0x2c, 0xb3, 0x1f, 0xe5, 0xc2, 0xce, 0x3b,
    0x5f, 0x64, 0xf5, 0x9d, 0x10, 0x65, 0x7e, 0xe3, 0xe1, 0x88, 0x92, 0x5c,
    0x7b, 0xf0, 0x68, 0x4d, 0x25, 0x2c, 0xdb, 0xfa, 0x74, 0x9d, 0x4d, 0xce,
    0x37, 0x0c, 0xd7, 0x25, 0xec, 0xe9, 0x75, 0x1b, 0xd2, 0x33, 0xf7, 0x5f,
    0xab, 0xce, 0xe0, 0x27, 0x4f, 0xe5, 0xd5, 0xa8};
const unsigned char key[] = {0x37, 0x69, 0x68, 0x4f, 0x4e, 0x6a, 0x32, 0x57, 0x4f, 0x39,
    0x6a, 0x6f, 0x73, 0x50, 0x46, 0x65};
#else
const char flag_easy[64] = "ph0wn{thumbv2_shellcoding_is_easy}\0";
const unsigned char flag_hard[64] = {0xd9, 0x45, 0x5a, 0x8d, 0x1e, 0x0, 0xb8,
    0x4e, 0x65, 0x37, 0x6a, 0xae, 0x62, 0x39, 0x7f, 0x38, 0xd2, 0xcf, 0xff,
    0x20, 0xfd, 0x9b, 0x0d, 0xfc, 0x54, 0x07, 0xa2, 0x14, 0xfc, 0x6f, 0x84,
    0x18, 0xda, 0x5e, 0x58, 0x75, 0xd8, 0x4a, 0x77, 0xf1, 0xa6, 0xd6, 0x01,
    0x7b, 0xde, 0xdc, 0x9d, 0xa6, 0x9d, 0x72, 0xc4, 0xb4, 0x3a, 0x5d, 0x8d,
    0xa7, 0xe2, 0x85, 0xa7, 0x69, 0xd3, 0x6d, 0xa2, 0xad}; 
const unsigned char __attribute__((section (".key"))) key[] = {0x4f, 0x43, 0x49, 0x50, 0x39, 0x54, 0x71, 0x75, 0x54, 0x59, 0x63, 0x64, 0x6c, 0x47, 0x4d, 0x69};
#endif





DigitalOut led1(LED1);
DigitalOut led2(LED2);
InterruptIn button1(BUTTON1);
InterruptIn button2(BUTTON2);
InterruptIn button3(BUTTON3);
InterruptIn button4(BUTTON4);


int op; // we use an int so that shellcode is padded correctly

char shellcode[SC_SIZE];

void print_menu(){
    puts("1) Help");
    puts("2) Read");
    puts("3) Exec");
    puts("4) Verify");
}

void read_shellcode(){
    printf("Shellcode: ");
    led2 = 0;
    
    for (unsigned short n = 0 ; n < SC_SIZE ; n++){
        read(0, &shellcode[n], 1); //shellcode[n] = getc(stdin);
        putc(shellcode[n], stdout);
        if (shellcode[n] == 0 || shellcode[n] == 0x0a)
            break;
        led2 = !led2;
    }

    led2 = 1;
    printf("\nOK.\n");

}


void exec_shellcode(){
    printf("Calling your shellcode...!\n");
    asm volatile("blx %0" : : "r" (shellcode+1));
    printf("That's it, we executed it!\n");
}

/* 
 * To prevent players from jumping into the verify function we put it into
 * a seperate section for the release version.  
 */
#if PUBLIC
void verify(){
#else
void __attribute__((section (".verify"))) verify(){
#endif
    unsigned char outbuf[64];

    mbedtls_aes_context aes;
    if (!strcmp(magic_easy, "MELLON")) {
        printf("You did the easy part! Congratz - get a flag as reward: %s\n",
                flag_easy);
    } else if(!strcmp(magic_hard, "MELLON")) {
        mbedtls_aes_setkey_dec( &aes, key, 128 );
        for (int i=0 ; i < 4 ; i++)
            mbedtls_aes_crypt_ecb(&aes, MBEDTLS_AES_DECRYPT, flag_hard +i*16, outbuf+i*16);

        printf("You did the harder part! Congratz - get a flag as reward: %s\n",
                outbuf);
    } else {
        printf("Nope!\n");
    }
}


void button1_pressed(){ op = 1; }
void button2_pressed(){ op = 2; }
void button3_pressed(){ op = 3; }
void button4_pressed(){ op = 4; }

void blinky(){ led1 = !led1; }

void setup(){

    led2 = 1;

    setvbuf(stdin, 0, 2, 0);
    setvbuf(stdout, 0, 2, 0);

    button1.fall(button1_pressed);
    button2.fall(button2_pressed);
    button3.fall(button3_pressed);
    button4.fall(button4_pressed);
}





int main(){
    Ticker t;

    t.attach(blinky, 1);
    op = 1;

    setup();
    
        puts("Speak, friend, and enter!!");

    while(1){
        switch(op) {
            case 1:
                print_menu();
                break;
            case 2:
                read_shellcode();
                break;
            case 3:
                exec_shellcode();
                break;
            case 4:
                verify();
            default:
                break;
        }
        op = 0;
        wait(0.1);
    }
}

