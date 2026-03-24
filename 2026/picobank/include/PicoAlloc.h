/*
 * PicoBankAlloc.h
 *
 *  Created on: Aug 25, 2025
 *      Author: rmalmain
 */

#ifndef PICOBANKALLOC_H_
#define PICOBANKALLOC_H_

#include <stdbool.h>
#include <stddef.h>

struct pico_heap_layout {
    bool valid;
    void* start;
    size_t len;
};

// init the allocator
void PicoAllocInit();

// use the allocator
void* PicoMalloc(size_t nb_bytes);
void PicoFree(void* data);
void PicoAllocPrint();

// check allocator correctness

// non-crashing version, returning true if everything is fine
bool PicoAllocCheck();
// crashing version, stopping execution on error
void PicoAllocCheckAssert();

#ifdef PICO_LINUX
struct pico_heap_layout pico_linux_map_heap(size_t alloc_size);
#endif

#endif /* PICOBANKALLOC_H_ */
