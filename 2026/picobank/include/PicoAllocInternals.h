#pragma once

#include <stdint.h>
#include <stddef.h>
#include <stdbool.h>
#include <string.h>

#ifdef PICO_DEBUG
#define PICO_ALLOC_DEBUG(fmt, ...) printf(fmt "\r\n", ##__VA_ARGS__)
#else
#define PICO_ALLOC_DEBUG(fmt, ...) do {} while(0)
#endif

#ifdef PICO_LINUX
#define PICO_HEAP_SIZE 8 * (1 << 20) // 8 MiB
#else
extern void _pvPicoHeapStart(void);
extern void _PicoHeapSize(void);

extern void _pvHeapStart(void);
extern void _HeapSize(void);

#define PICO_HEAP_START ((void*)&_pvPicoHeapStart)
#define PICO_HEAP_SIZE ((size_t) &_PicoHeapSize)

#define PICO_SECRET_START ((uintptr_t*) &_pvHeapStart)
#define PICO_SECRET_NB_ELTS ((PICO_HEAP_SIZE >> 3))
#define PICO_SECRET_SIZE (PICO_SECRET_NB_ELTS * sizeof(uintptr_t))

#endif

#define PB_FLAG_PREV_INUSE (1 << 0)

#define MINSIZE (sizeof(struct PBChunk))

#define PICO_SECRET_INVALID ((uintptr_t) -1)

// get the real size to allocate from the requested size (the usable size)
#define req2size(req) ((req + sizeof(struct PBChunkHdr)) < MINSIZE) ? MINSIZE : (req + sizeof(struct PBChunkHdr))

#define container_of(ptr, type, member) ({ \
    (type *)( (char *)(ptr) - offsetof(type, member) ); \
})

#define CHUNK2DATA(chunk_ptr) (&chunk_ptr->data)

#define DATA2CHUNK(data_ptr) (container_of(data_ptr, struct PBChunk, data))

#define NEXTCHUNK(chunk_ptr) ((struct PBChunk*) ((char*) chunk_ptr + chunk_ptr->hdr.size))

#define RESETCHUNK(chunk_ptr) (memset(chunk_ptr, 0, MINSIZE))

// header, in every chunk
struct PBChunkHdr {
    uintptr_t hash; // hash of the current chunk
	size_t size; // chunk size minimum size is the size of (hash + size + next + prev).
	struct PBChunk* next; // alloc list if in use, free list if free
};

struct PBChunk {
    // the header of the chunk, with some metadata
	struct PBChunkHdr hdr;
	char data[];
};

struct PBAllocator {
    bool initialized;
	void* mem;
	size_t size;
	struct PBChunk* alloc_list; // single linked list
	struct PBChunk* free_list; // double linked list
	uintptr_t* secrets; // should be stored in a "safe" memory
};
