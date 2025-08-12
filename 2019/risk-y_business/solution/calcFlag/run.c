#include <stdio.h>
#include <stdlib.h>
#include <stdint.h> 
#include <string.h>
#include "sha3.h"

//fake sha3:
sha3_context c;
uint8_t *hash;

uint8_t key[32]= "ph0wn{UVeJustWroteUr1stR5Code!}\0";
uint8_t flag[32]="ph0wn{xXxXxXxXxXxXxXxXxXxXxXxX}\0";
//uint8_t key[32]={0xc1,0xb7,0xa0,0x1b,0x98,0x9c,0xd4,0x77,0xf7,0x16,0x84,0xa9,0x67,0xab,0xde,0xe,0xf3,0xf9,0x7d,0xe3,0xcb,0x29,0x10,0x54,0x22,0xd3,0xb9,0xfb,0x27,0xcb,0xda,0xa3};
int16_t i;

void main()
{
    /* compute the flag */
    for (i=0;i<666;i++)
    {
        sha3_Init256(&c);
        sha3_Update(&c, (void const*)flag, 32);
        hash = (uint8_t*)sha3_Finalize(&c);
        memcpy(flag,hash,32);
    }

    /* unxor the flag */
    for (i=0;i<32;i++)
        flag[i]^=key[i];

    printf("\nkey:");
    for (i=0;i<32;i++)
	printf("%x ",flag[i]);

    printf("\n%s\n",flag);
}

