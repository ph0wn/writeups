#include<stdio.h>
#include<stdlib.h>
#include<sys/mman.h>
#include<assert.h>
#include<string.h>
#include<stdint.h>
#include<math.h>

#define WITHDEBUG 0

// clear most registers
#define PREFIX "\x00\x00\x20\xe0\x01\x10\x21\xe0\x02\x20\x22\xe0\x03\x30\x23\xe0\x04\x40\x24\xe0\x05\x50\x25\xe0\x06\x60\x26\xe0\x07\x70\x27\xe0\x08\x80\x28\xe0\x09\x90\x29\xe0\x0a\xa0\x2a\xe0\x0b\xb0\x2b\xe0\x0c\xc0\x2c\xe0\x0e\xe0\x2e\xe0"
#define PREFIXSIZE ((unsigned int) sizeof(PREFIX)-1) // we don't care about \0

#define PAYLOADSIZE 420

#define SUFFIX "\x01\x00\xa0\xe3\x01\x70\xa0\xe3\x00\x00\x00\xef"
#define SUFFIXSIZE ((unsigned int) sizeof(SUFFIX)-1) // we don't care about \0

#define BUFADDR 0x42420000
#define BUFSIZE (PREFIXSIZE + PAYLOADSIZE + SUFFIXSIZE)

#if WITHDEBUG
void foo() {
}
#endif

int main() {
    uint8_t *buf;
    uint8_t rnd[4];
    FILE *fp;
    int i;

    setbuf(stdin, 0);
    setbuf(stdout, 0);
    setbuf(stderr, 0);

    fp = fopen("/dev/urandom", "rb");
    if (fp == NULL) {
        printf("Can't open /dev/urandom. Ping admins.\n");
        exit(1);
    }

    for (i=0; i<4; i++) {
        int n = fread(&(rnd[i]), 1, 1, fp);
        if (n != 1) {
            printf("Can't read from /dev/urandom. Ping admins.\n");
            exit(1);
        }
    }
    fclose(fp);

    // leak them
    for (i=0; i<4; i++) {
        fwrite(&rnd[i], 1, 1, stdout);
    }

    buf = (uint8_t *) mmap((void*) BUFADDR, BUFSIZE, PROT_READ | PROT_WRITE, MAP_PRIVATE|MAP_ANONYMOUS|MAP_FIXED, 0, 0);
    if (buf == NULL || buf == MAP_FAILED) {
        printf("Error mmaping. Contact admins.\n");
        exit(1);
    }
#if WITHDEBUG
    printf("buf @ %p, size: %d\n", buf, BUFSIZE);
#endif

    memcpy(buf, PREFIX, PREFIXSIZE);
    memcpy(buf+PREFIXSIZE+PAYLOADSIZE, SUFFIX, SUFFIXSIZE);

    for (i=0; i<PAYLOADSIZE;) {
        int n = fread(&(buf[i+PREFIXSIZE]), 1, 1, stdin);
        if (n < 0) {
            printf("Error: I need more bytes\n");
            exit(1);
        }
        if (n == 1) {
            i++;
        }
    }
#if WITHDEBUG
    printf("Done reading bytes\n");
#endif

    // scan for bx and blx and reject if any. bx and blx finish with \xe1
    for (i=PREFIXSIZE; i<PREFIXSIZE+PAYLOADSIZE; i+=4) {
        unsigned int val = *(unsigned int *)(&buf[i]);
        unsigned int masked = val & 0x0ffffff0; // remove operandcond and register
        if (masked == 0x12fff10 /* bx reg */ || masked == 0x12fff30 /* blx reg */ || masked == 0xafffff0 /* blx label */) {
            printf("Nice try, but no thumb for you.\n");
            exit(1);
        }
    }

    // scan for "sh" and flag"
    for (i=PREFIXSIZE; i<PREFIXSIZE+PAYLOADSIZE; i++) {
        if (strncmp(&buf[i], "sh", 2) == 0) {
            printf("No shells allowed!\n");
            exit(1);
        }
        if (strncmp(&buf[i], "flag", 4) == 0) {
            printf("Flags? really?\n");
            exit(1);
        }
    }
#if WITHDEBUG
    printf("passed the checks");
#endif

    // build pattern:
    // 0: player's instruction
    // 4: b<cond> exit
    // 8: player's instruction
    // c: b<cond> exit
    // ...
    // where "exit" points at the beginning of the SUFFIX
    unsigned int targetoff = PAYLOADSIZE;
    uint8_t cond = (rnd[0] ^ rnd[1] ^ rnd[2] ^ rnd[3]) & 0x3; // take only 2 bits, init state can be 0..4
#if WITHDEBUG
    printf("cond: %d\n", cond);
#endif
    for (i=0; i<PAYLOADSIZE/8; i++) {
        unsigned int curroff = i*8+4;
        int x = (targetoff-curroff) / 4 - 2;
        if (x >= 0) {
            buf[PREFIXSIZE+i*8+4] = (char) x;
            buf[PREFIXSIZE+i*8+4+1] = '\x00';
            buf[PREFIXSIZE+i*8+4+2] = '\x00';
        } else if (x == -1) {
            buf[PREFIXSIZE+i*8+4] = '\xff';
            buf[PREFIXSIZE+i*8+4+1] = '\xff';
            buf[PREFIXSIZE+i*8+4+2] = '\xff';
        } else {
            printf("Error\n");
            exit(1);
        }
        buf[PREFIXSIZE+i*8+4+3] = ((0xea & 0x0f) | ((cond++ << 4) & 0xf0));
    }

    // make it read-only
    mprotect((void*) BUFADDR, BUFSIZE, PROT_READ | PROT_EXEC);

#if WITHDEBUG
    printf("Jumping on shellcode\n");

    foo();
#endif

    printf("BAM!\n");

    ((void (*)()) buf) ();
    exit(0);
}
