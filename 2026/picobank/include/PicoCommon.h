#pragma once

#ifdef PICO_LINUX

#include <stdio.h>
#include <unistd.h>
#include <sys/random.h>


#ifndef PRINTF
#define PRINTF(fmt, ...) printf(fmt "\r\n", ##__VA_ARGS__)
#endif

#else

#include "fsl_common.h"
#include "fsl_debug_console.h"

#define PPRINTF(fmt, ...) PRINTF(fmt "\r\n", ##__VA_ARGS__)

#ifdef PICO_DEBUG
#define PDEBUG(fmt, ...) PRINTF(fmt "\r\n", ##__VA_ARGS__)
#else
#define PDEBUG(fmt, ...) do {} while(0)
#endif

#endif

void PicoWfe(void);
