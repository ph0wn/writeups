/*
 * Copyright (c) 2016, Freescale Semiconductor, Inc.
 * Copyright 2016-2020 NXP
 * All rights reserved.
 *
 *
 * SPDX-License-Identifier: BSD-3-Clause
 */

/*******************************************************************************
 * Includes
 ******************************************************************************/
#include "lwip/opt.h"

#if LWIP_TCP

#include "lwip/apps/httpd.h"
#include "lwip/timeouts.h"
#include "lwip/init.h"
#include "netif/ethernet.h"
#include "enet_ethernetif.h"

#include "board.h"
#include "fsl_phy.h"

#include "pin_mux.h"
#include "clock_config.h"
#include "fsl_gpio.h"
#include "fsl_iomuxc.h"
#include "fsl_phyksz8081.h"
#include "fsl_enet_mdio.h"

//Phil
#include "lwip/stats.h"
#include "lwip/apps/httpd_opts.h"
#include <stdio.h>

/*******************************************************************************
 * Definitions
 ******************************************************************************/
/* IP address configuration. */
#define configIP_ADDR0 192
#define configIP_ADDR1 168
#define configIP_ADDR2 0
#define configIP_ADDR3 90

/* Netmask configuration. */
#define configNET_MASK0 255
#define configNET_MASK1 255
#define configNET_MASK2 255
#define configNET_MASK3 0

/* Gateway address configuration. */
#define configGW_ADDR0 192
#define configGW_ADDR1 168
#define configGW_ADDR2 0
#define configGW_ADDR3 254

/* MAC address configuration. */
#define configMAC_ADDR                     \
    {                                      \
        0x02, 0x12, 0x13, 0x10, 0x15, 0x11 \
    }

/* Address of PHY interface. */
#define EXAMPLE_PHY_ADDRESS BOARD_ENET0_PHY_ADDRESS

/* MDIO operations. */
#define EXAMPLE_MDIO_OPS enet_ops

/* PHY operations. */
#define EXAMPLE_PHY_OPS phyksz8081_ops

/* ENET clock frequency. */
#define EXAMPLE_CLOCK_FREQ CLOCK_GetFreq(kCLOCK_IpgClk)

#ifndef EXAMPLE_NETIF_INIT_FN
/*! @brief Network interface initialization function. */
#define EXAMPLE_NETIF_INIT_FN ethernetif0_init
#endif /* EXAMPLE_NETIF_INIT_FN */

/*******************************************************************************
 * Prototypes
 ******************************************************************************/

/*******************************************************************************
 * Variables
 ******************************************************************************/

static mdio_handle_t mdioHandle = {.ops = &EXAMPLE_MDIO_OPS};
static phy_handle_t phyHandle   = {.phyAddr = EXAMPLE_PHY_ADDRESS, .mdioHandle = &mdioHandle, .ops = &EXAMPLE_PHY_OPS};

/*******************************************************************************
 * Code
 ******************************************************************************/

/* ADD by Phil */

// CGI

struct {
	char FLAG[30];
	char zoom[12];
	char speed[12];
	char debug[12];
	uint32_t *pokeAdr;
	uint32_t pokeValue;
	uint32_t *madPointer;
} MyGlobalRAM;

	char flagLVL2[25];

/* Common Gateway Interface (CG) handler ..................................*/
static char const *cgi_demo(int index, int numParams,
                               char *param[],
                               char *value[])
{
    int i;
    strcpy(MyGlobalRAM.zoom,"50");
    strcpy(MyGlobalRAM.speed,"20");
	//PRINTF("call demo CGI\r\n");
	//PRINTF("numParams: %d\r\n",numParams);
    for (i = 0; i < numParams; ++i) {
    	//PRINTF("param[i]: %s = value[i]: %s\r\n",param[i],value[i]);
        if (strncmp(param[i], "zoom", 4) == 0) {
        	strncpy(MyGlobalRAM.zoom,value[i],11);
        } else if (strncmp(param[i], "speed", 5) == 0) {
        	strncpy(MyGlobalRAM.speed,value[i],11);
        }
    }
   	return ("/demo.shtml");
}

static char const *cgi_hidden(int index, int numParams,
                               char *param[],
                               char *value[])
{
    int i;
    int goesPoke=0;
    uint32_t stackA = 0;
    strcpy(MyGlobalRAM.debug,"3");
    MyGlobalRAM.pokeAdr = (uint32_t*)0x20010000;
    MyGlobalRAM.pokeValue = 0xCAFEC0CA;
    MyGlobalRAM.madPointer = (uint32_t*)&MyGlobalRAM.madPointer;

    for (i = 0; i < numParams; ++i) {
        if (strncmp(param[i], "debug", 5) == 0) {
        	if ( (value[i][0] >='0') && (value[i][0] <='2') ) {
        		strncpy(MyGlobalRAM.debug,value[i],11);
				switch (value[i][0]) {
					case '0':
						asm volatile ("mov %0, sp\n\t"
							 : "=r" ( stackA)
							 );
						MyGlobalRAM.madPointer = &stackA;
						break;
					case '1':
						MyGlobalRAM.madPointer = (uint32_t*)0x60002000;
						break;
					case '2':
						MyGlobalRAM.madPointer = (uint32_t*)0x60002004;;
						break;
				}
        	}
        	else { // debug is out of 0..2
        		MyGlobalRAM.debug[0]='3';
        	}
        } else if (strncmp(param[i], "pokeAdr", 7) == 0) {
        	goesPoke|=0x1;
        	sscanf(value[i],"%X",&MyGlobalRAM.pokeAdr);
        } else if (strncmp(param[i], "pokeValue", 9) == 0) {
        	goesPoke|=0x2;
        	sscanf(value[i],"%X",&MyGlobalRAM.pokeValue);
        }
    }
    if (goesPoke == 0x3)
    	*MyGlobalRAM.pokeAdr=MyGlobalRAM.pokeValue;
    //return (char *)0;/*no URI, HTTPD will send 404 error page to the browser*/
    return ("/h1dden.shtml");

}

static tCGI const cgi_handlers[] = {
	    { "/demo.cgi", &cgi_demo },
	    { "/h1dd3n.cgi", &cgi_hidden },
};



// SSI

static char const * ssi_tags[] = {
    "s_xmit",
    "s_recv",
    "s_fw",
    "s_drop",
    "s_chkerr",
    "s_lenerr",
    "s_memerr",
    "s_rterr",
    "s_proerr",
    "s_opterr",
    "s_err",
	"zoom",
	"speed",
	"debugL",
	"debugV"
};

// no more include tag in the html file served
//#define LWIP_HTTPD_SSI_INCLUDE_TAG           0

static u16_t ssi_handler(int iIndex, char *pcInsert, int iInsertLen) {
    struct stats_proto *stats = &lwip_stats.link;
    STAT_COUNTER value = 0;

    if (iIndex < 11) {
		switch (iIndex) {
			case 0:                                                 /* s_xmit   */
				value = stats->xmit;
				break;
			case 1:                                                 /* s_recv   */
				value = stats->recv;
				break;
			case 2:                                                 /* s_fw     */
				value = stats->fw;
				break;
			case 3:                                                 /* s_drop   */
				value = stats->drop;
				break;
			case 4:                                                 /* s_chkerr */
				value = stats->chkerr;
				break;
			case 5:                                                 /* s_lenerr */
				value = stats->lenerr;
				break;
			case 6:                                                 /* s_memerr */
				value = stats->memerr;
				break;
			case 7:                                                 /* s_rterr  */
				value = stats->rterr;
				break;
			case 8:                                                 /* s_proerr */
				value = stats->proterr;
				break;
			case 9:                                                 /* s_opterr */
				value = stats->opterr;
				break;
			case 10:                                                /* s_err    */
				value = stats->err;
				break;
		}

		return snprintf(pcInsert, LWIP_HTTPD_MAX_TAG_INSERT_LEN, "%d", value);
    }
    else {
		switch (iIndex) {
			case 11:                                                 /* zoom   */
				return snprintf(pcInsert, LWIP_HTTPD_MAX_TAG_INSERT_LEN, "%s", MyGlobalRAM.zoom);
				break;
			case 12:                                                 /* speed  */
				return snprintf(pcInsert, LWIP_HTTPD_MAX_TAG_INSERT_LEN, "%s", MyGlobalRAM.speed);
				break;
			case 13:                                                 /* debugL  */
				switch (MyGlobalRAM.debug[0]) {
					case '0':
						return snprintf(pcInsert, LWIP_HTTPD_MAX_TAG_INSERT_LEN, "Stack pointer");
						break;
					case '1':
						return snprintf(pcInsert, LWIP_HTTPD_MAX_TAG_INSERT_LEN, "Stack start (0x60002000)");
						break;
					case '2':
						return snprintf(pcInsert, LWIP_HTTPD_MAX_TAG_INSERT_LEN, "Reset vector (0x60002004)");
						break;
					case '3':
						return snprintf(pcInsert, LWIP_HTTPD_MAX_TAG_INSERT_LEN, "Unknown address");
						break;
				}
				break;
			case 14:                                                 /* debugV  */
				switch (MyGlobalRAM.debug[0]) {
					case '0':
						return snprintf(pcInsert, LWIP_HTTPD_MAX_TAG_INSERT_LEN, "0x%X", MyGlobalRAM.madPointer);
						break;
					case '1':
					case '2':
						return snprintf(pcInsert, LWIP_HTTPD_MAX_TAG_INSERT_LEN, "0x%X", *MyGlobalRAM.madPointer);
						break;
					case '3':
						return snprintf(pcInsert, LWIP_HTTPD_MAX_TAG_INSERT_LEN, "0x%X", 0xDEADBEEF);
						break;
				}
				break;
//			case 15:	// place keeper, never called
//				return snprintf(pcInsert, LWIP_HTTPD_MAX_TAG_INSERT_LEN, "%s", flagLVL2);
		}
    }
	return snprintf(pcInsert, LWIP_HTTPD_MAX_TAG_INSERT_LEN, "%d", 0);	// print 0
}


/* ADD by Phil */


void BOARD_InitModuleClock(void)
{
    const clock_enet_pll_config_t config = {.enableClkOutput = true, .enableClkOutput25M = false, .loopDivider = 1};
    CLOCK_InitEnetPll(&config);
}

void delay(void)
{
    volatile uint32_t i = 0;
    for (i = 0; i < 1000000; ++i)
    {
        __asm("NOP"); /* delay */
    }
}



/*!
 * @brief Interrupt service for SysTick timer.
 */
void SysTick_Handler(void)
{
    time_isr();
}

/*!
 * @brief Main function
 */
int main(void)
{
    struct netif netif;
    ip4_addr_t netif_ipaddr, netif_netmask, netif_gw;
#if defined(FSL_FEATURE_SOC_LPC_ENET_COUNT) && (FSL_FEATURE_SOC_LPC_ENET_COUNT > 0)
    mem_range_t non_dma_memory[] = NON_DMA_MEMORY_ARRAY;
#endif /* FSL_FEATURE_SOC_LPC_ENET_COUNT */
    ethernetif_config_t enet_config = {
        .phyHandle  = &phyHandle,
        .macAddress = configMAC_ADDR,
#if defined(FSL_FEATURE_SOC_LPC_ENET_COUNT) && (FSL_FEATURE_SOC_LPC_ENET_COUNT > 0)
        .non_dma_memory = non_dma_memory,
#endif /* FSL_FEATURE_SOC_LPC_ENET_COUNT */
    };

    gpio_pin_config_t gpio_config = {kGPIO_DigitalOutput, 0, kGPIO_NoIntmode};

    BOARD_ConfigMPU();
    BOARD_InitBootPins();
    BOARD_InitBootClocks();
    BOARD_InitDebugConsole();
    BOARD_InitModuleClock();

    IOMUXC_EnableMode(IOMUXC_GPR, kIOMUXC_GPR_ENET1TxClkOutputDir, true);

    GPIO_PinInit(GPIO1, 9, &gpio_config);
    GPIO_PinInit(GPIO1, 10, &gpio_config);
    /* pull up the ENET_INT before RESET. */
    GPIO_WritePinOutput(GPIO1, 10, 1);
    GPIO_WritePinOutput(GPIO1, 9, 0);
    delay();
    GPIO_WritePinOutput(GPIO1, 9, 1);

    mdioHandle.resource.csrClock_Hz = EXAMPLE_CLOCK_FREQ;

    time_init();

    PRINTF("\r\nRunning!\r\n");

    IP4_ADDR(&netif_ipaddr, configIP_ADDR0, configIP_ADDR1, configIP_ADDR2, configIP_ADDR3);
    IP4_ADDR(&netif_netmask, configNET_MASK0, configNET_MASK1, configNET_MASK2, configNET_MASK3);
    IP4_ADDR(&netif_gw, configGW_ADDR0, configGW_ADDR1, configGW_ADDR2, configGW_ADDR3);

    lwip_init();

    netif_add(&netif, &netif_ipaddr, &netif_netmask, &netif_gw, &enet_config, EXAMPLE_NETIF_INIT_FN, ethernet_input);
    netif_set_default(&netif);
    netif_set_up(&netif);

    httpd_init();

	#define Q_DIM(array_) (sizeof(array_) / sizeof((array_)[0]))
    http_set_cgi_handlers(cgi_handlers, Q_DIM(cgi_handlers));
    http_set_ssi_handler(&ssi_handler, ssi_tags, Q_DIM(ssi_tags));

    strcpy(flagLVL2,"ph0wn{easierWithPeek}");

    PRINTF("\r\n************************************************\r\n");
    PRINTF(" ph0wn's embedded HTTP server\r\n");
    PRINTF("************************************************\r\n");
    PRINTF(" IPv4 Address     : %u.%u.%u.%u\r\n", ((u8_t *)&netif_ipaddr)[0], ((u8_t *)&netif_ipaddr)[1],
           ((u8_t *)&netif_ipaddr)[2], ((u8_t *)&netif_ipaddr)[3]);
    PRINTF(" IPv4 Subnet mask : %u.%u.%u.%u\r\n", ((u8_t *)&netif_netmask)[0], ((u8_t *)&netif_netmask)[1],
           ((u8_t *)&netif_netmask)[2], ((u8_t *)&netif_netmask)[3]);
    PRINTF(" IPv4 Gateway     : %u.%u.%u.%u\r\n", ((u8_t *)&netif_gw)[0], ((u8_t *)&netif_gw)[1],
           ((u8_t *)&netif_gw)[2], ((u8_t *)&netif_gw)[3]);
    PRINTF("************************************************\r\n");

    // set debug default
    MyGlobalRAM.debug[0]='3';

    // generate the filesystem:
    // phil@maxi:~/ph0wn/nxp/evkbimxrt1050_lwip_httpsrv_bm/lwip/src/apps/http/makefsdata

    while (1)
    {
        /* Poll the driver, get any outstanding frames */
        ethernetif_input(&netif);

        sys_check_timeouts(); /* Handle all system timeouts for all core protocols */
    }
}
#endif /* LWIP_TCP */
