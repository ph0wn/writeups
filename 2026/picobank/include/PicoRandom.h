#pragma once

#include <stdint.h>
#include <stdbool.h>

void PicoRandomInit(void);
bool PicoRandomIsInitialized(void);
uintptr_t PicoRandomGet(void);
