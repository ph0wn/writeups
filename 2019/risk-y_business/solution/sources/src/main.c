/*!
    \file  main.c
    \brief USART transmit and receive interrupt

    \version 2019-06-05, V1.0.0, firmware for GD32VF103
*/

/*
    Copyright (c) 2019, GigaDevice Semiconductor Inc.

    Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice, this 
       list of conditions and the following disclaimer.
    2. Redistributions in binary form must reproduce the above copyright notice, 
       this list of conditions and the following disclaimer in the documentation 
       and/or other materials provided with the distribution.
    3. Neither the name of the copyright holder nor the names of its contributors 
       may be used to endorse or promote products derived from this software without 
       specific prior written permission.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" 
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED 
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. 
IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, 
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR 
PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, 
WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) 
ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY 
OF SUCH DAMAGE.
*/

#include "gd32vf103.h"
#include <stdio.h>
#include "gd32vf103v_eval.h"
#include "systick.h"
#include <string.h>
#include "sha3.h"

#define ARRAYNUM(arr_nanme)      (uint32_t)(sizeof(arr_nanme) / sizeof(*(arr_nanme)))
#define WM_SIZE            (ARRAYNUM(welcome_message) - 1)
#define GI_SIZE            (ARRAYNUM(got_it) - 1)
#define RXBUFFER_SIZE   50
const uint8_t welcome_message[] = "\n\r\
                                           %                                   \r\n\
                          %.            *                                      \r\n\
                             @         #                                       \r\n\
                               &                                               \r\n\
                                .                                              \r\n\
                         %((((         #(((%                                   \r\n\
                       (((((((#      #(((((//                                  \r\n\
                      (/(((((((      &(((((//(                                 \r\n\
                      @%%((((((      ,((((((@@,                                \r\n\
                      @@(((((((       #((((#/@                                 \r\n\
                      %/(((((((((((((((((((%(                                  \r\n\
                        ((((((((((((((((((((                                   \r\n\
                        ((((((((((((((((((((((((((((((((((((((((((((((#        \r\n\
                         ((% (((((((((((((((((((((((((((((((((((((((((         \r\n\
                         (....   .(%%%#((((((((#(((((((((((#####(*. )          \r\n\
                          ,...... ....                             ,           \r\n\
                           (..... ...........    .....    .....  )             \r\n\
                            %.................      ........... .              \r\n\
                             (................................)                \r\n\
                               &........................... %                  \r\n\
                                 ,,......................)                     \r\n\
                                  ...%,..............*)                        \r\n\
                                       .........,                              \r\n\
                                                                               \r\n\
Send your shellcode, 50 bytes:";
const uint8_t got_it[] = "\n\rShellcode received successfully!\n\rNow, the big jump ...\n\r";
//uint8_t rxbuffer[RXBUFFER_SIZE];
uint16_t tx_size = WM_SIZE;
uint8_t rx_size = RXBUFFER_SIZE;
__IO uint16_t txcount = 0; 
__IO uint16_t rxcount = 0; 
uint8_t to_send=1;
int16_t i;

/* fake sha3 */
sha3_context c;
uint8_t *hash;

uint8_t flag[32]="ph0wn{xXxXxXxXxXxXxXxXxXxXxXxX}\0";
uint8_t key[32]={0xc1,0xb7,0xa0,0x1b,0x98,0x9c,0xd4,0x77,0xf7,0x16,0x84,0xa9,0x67,0xab,0xde,0xe,0xf3,0xf9,0x7d,0xe3,0xcb,0x29,0x10,0x54,0x22,0xd3,0xb9,0xfb,0x27,0xcb,0xda,0xa3};
uint8_t shellcode[50];

/*!
    \brief      send a simple byte over USART0
    \param[in]  ch = char to send
    \param[out] none
    \retval     none
*/
void put_char(uint8_t ch)
{
    usart_data_transmit(USART0, ch);
    while(RESET == usart_flag_get(USART0, USART_FLAG_TC));

}

/*!
    \brief      main function
    \param[in]  none
    \param[out] none
    \retval     none
*/
int main(void)
{
    /* USART interrupt configuration */
    eclic_global_interrupt_enable();
    eclic_priority_group_set(ECLIC_PRIGROUP_LEVEL3_PRIO1);
    eclic_irq_enable(USART0_IRQn, 1, 0);
    /* configure COM0 */
    gd_eval_com_init(EVAL_COM0);

    /* enable USART TBE interrupt */  
    usart_interrupt_enable(USART0, USART_INT_TBE);
    
    /* wait until USART send the transmitter_buffer */
    while(txcount < tx_size);
    /* transmit last byte */
    while(RESET == usart_flag_get(USART0, USART_FLAG_TC));
    /* stop USART TBE interrupt */  
    usart_interrupt_disable(USART0, USART_INT_TBE);

    /* stop receive interrupt */
    usart_interrupt_enable(USART0, USART_INT_RBNE);

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
 
    /* wait until USART receive the receiver_buffer */
    while(rxcount < rx_size);
    if(rxcount == rx_size)
    {
        /* stop receive interrupt */
        usart_interrupt_disable(USART0, USART_INT_RBNE);
        to_send = 2;
        txcount = 0;
        tx_size = GI_SIZE;
        /* enable USART TBE interrupt */  
        usart_interrupt_enable(USART0, USART_INT_TBE);
        /* wait until USART send the transmitter_buffer */
        while(txcount < tx_size);
        /* transmit last byte */
        while(RESET == usart_flag_get(USART0, USART_FLAG_TC));
        /* stop USART TBE interrupt */  
        usart_interrupt_disable(USART0, USART_INT_TBE);
    }

    delay_1ms(2000);
    put_char('G');
    put_char('O');
    put_char('\r');
    put_char('\n');

    /* call the shellcode */
    (*(void(*)()) shellcode) ();

    while(1);

}

