#include <stdio.h>
#include <stdbool.h>
#include <stdint.h>
#include <stddef.h>
#include <stdlib.h>

#include "PicoAlloc.h"
#include "PicoCommon.h"
#include "PicoRandom.h"

#include "PicoAllocInternals.h"

static struct PBAllocator alloc;

static uintptr_t* chunk2secret(struct PBChunk* chunk)
{
	PDEBUG("Checking chunk @addr %p", chunk);

    if (alloc.secrets != PICO_SECRET_START) {
    	PicoWfe();
    }

    // each chunk is at least 8 bytes, so this is valid
    uintptr_t index = (((char*) chunk) - (char*) alloc.mem) >> 3;

    if (index >= PICO_SECRET_NB_ELTS) {
    	PicoWfe();
    }

    return &alloc.secrets[index];
}

static uintptr_t get_secret(struct PBChunk* chunk)
{
    if (alloc.secrets == NULL) {
        PDEBUG("Error: secret array uninitialized.");
        PicoWfe();
    }

    return *chunk2secret(chunk);
}

static void set_secret(struct PBChunk* chunk, uintptr_t secret)
{
    if (alloc.secrets == NULL) {
        PDEBUG("Error: secret array uninitialized.");
        PicoWfe();
    }

    uintptr_t* stored_secret = chunk2secret(chunk);

    *stored_secret = secret;
}

static void alloc_secret(struct PBChunk* chunk)
{
    uintptr_t random = PicoRandomGet();
    set_secret(chunk, random);
}

static struct pico_heap_layout get_heap_layout()
{
    static struct pico_heap_layout layout = {0};

    if (!layout.valid) {
#ifdef PICO_LINUX
        layout = pico_linux_map_heap(PICO_HEAP_SIZE);
#else
        layout.len = PICO_HEAP_SIZE;
        layout.start = PICO_HEAP_START;
        layout.valid = true;
#endif
    }

    if (!layout.valid) {
        PDEBUG("INTERNAL BUG: invalid heap layout");
        PicoWfe();
    }

    return layout;
}

// get the usable size of a chunk
static inline size_t memsize(struct PBChunk* chunk)
{
	return chunk->hdr.size - sizeof(struct PBChunkHdr);
}

#if UINTPTR_MAX == 0xFFFFFFFF
// 32 bits
static uint32_t hash32(uint32_t x)
{
    x ^= x >> 17;
    x *= 0xed5ad4bbU;
    x ^= x >> 11;
    x *= 0xac4c1b51U;
    x ^= x >> 15;
    x *= 0x31848babU;
    x ^= x >> 14;

    return x;
}
#elif UINTPTR_MAX == 0xFFFFFFFFFFFFFFFFu
// 64 bits
static uint64_t hash64(uint64_t x)
{
    x ^= x >> 30;
    x *= 0xbf58476d1ce4e5b9;
    x ^= x >> 27;
    x *= 0x94d049bb133111eb;
    x ^= x >> 31;

    return x;
}
#else
#error "Unknown architecture ptr size"
#endif

static uintptr_t picohash_fast(uintptr_t x)
{
#if UINTPTR_MAX == 0xFFFFFFFF
    return hash32(x);
#elif UINTPTR_MAX == 0xFFFFFFFFFFFFFFFFu
    return hash64(x);
#else
#error "Unknown architecture ptr size"
#endif
}

static uintptr_t PicoHash(struct PBChunk* chunk)
{
	return picohash_fast((uintptr_t) chunk->hdr.next) ^ picohash_fast(chunk->hdr.size) ^ get_secret(chunk);
}

static void PicoAllocHeapSmashed()
{
    PPRINTF("*** heap smashing detected ***");
    PicoWfe();
}

bool PicoAllocCheckChunk(struct PBChunk* chunk)
{
    uintptr_t hash = PicoHash(chunk);
	if (chunk->hdr.hash != hash) {
	    PDEBUG("ERROR: stored hash (0x%lx) != chunk hash (0x%lx)", chunk->hdr.hash, hash);
		return false;
	}

	return true;
}

void PicoAllocCheckChunkAssert(struct PBChunk* chunk)
{
    if(!PicoAllocCheckChunk(chunk)) {
        PicoAllocHeapSmashed();
    }
}

static bool CheckHashesChunk(struct PBChunk* chunk)
{
	while(chunk != NULL) {
		if (!PicoAllocCheckChunk(chunk)) {
    		return false;
		}

		chunk = chunk->hdr.next;
	}

	return true;
}

// true -> check is successful
// false -> a hash is incorrect
bool PicoAllocCheck()
{
    // check alloc list
    PDEBUG("Checking alloc list...");
    PicoAllocPrint();
	if (!CheckHashesChunk(alloc.alloc_list)) {
		return false;
	}

	// check free list
	PDEBUG("Checking free list...");
	if (!CheckHashesChunk(alloc.free_list)) {
		return false;
	}

	return true;
}

void PicoAllocCheckAssert()
{
	if (!PicoAllocCheck()) {
    	PicoAllocHeapSmashed();
	}
}

static void UpdateHash(struct PBChunk* chunk)
{
    alloc_secret(chunk);
    chunk->hdr.hash = PicoHash(chunk);
}

void PicoAllocInit()
{
	if (alloc.initialized) {
    	return;
	}

	PPRINTF("[PicoAllocator] Initializing...");
	PPRINTF("\tVersion: V6.6.6");
	PPRINTF("\tHardening: ENABLED");

	struct pico_heap_layout layout = get_heap_layout();

	size_t secret_map_sz = PICO_SECRET_SIZE;

	if (secret_map_sz > (size_t)&_HeapSize) {
		PPRINTF("Error: not enough space: 0x%lx vs 0x%lx", secret_map_sz, _HeapSize);
		PicoWfe();
	}

	alloc.secrets = PICO_SECRET_START;

	if (alloc.secrets == NULL) {
		PPRINTF("Alloc failed.");
		PDEBUG("Malloc is most likely not initialized");
		PicoWfe();
	}

	alloc.size = layout.len;
	alloc.mem = layout.start;

	alloc.free_list = (struct PBChunk*) alloc.mem;
	alloc.free_list->hdr.size = alloc.size;
	alloc.free_list->hdr.next = NULL;

	// store the hash
	UpdateHash(alloc.free_list);

	// no initial allocation
	alloc.alloc_list = NULL;

	PDEBUG("PicoAlloc initialized:");
	PDEBUG("\tPBStart: %p", alloc.mem);
	PDEBUG("\tPBSize: 0x%lx", alloc.size);
	PDEBUG("\tChunk hdr size: 0x%lx", sizeof(struct PBChunkHdr));
	PDEBUG("\tChunk size: 0x%lx", sizeof(struct PBChunk));
	PDEBUG("\tChunk min size: 0x%lx", MINSIZE);

	alloc.initialized = true;

	PPRINTF("[PicoAllocator] Initialization done.");
	PPRINTF("");
}

static void alloc_append(struct PBChunk* chunk)
{
	if (alloc.alloc_list == NULL) {
		alloc.alloc_list = chunk;
	} else {
		struct PBChunk* current = alloc.alloc_list;

		while(current->hdr.next != NULL) {
			current = current->hdr.next;
		}

		current->hdr.next = chunk;
		UpdateHash(current);
	}
}

void* PicoMalloc(size_t nb_bytes)
{
	struct PBChunk* current = alloc.free_list;
	struct PBChunk* best = NULL;
	size_t min_size = alloc.size;

	PicoAllocCheckAssert();

	// find the best free chunk available
	// we will take the chunk that can fit in the smallest available free chunk
	while (current != NULL) {
		size_t usable_size = memsize(current);

		if (usable_size >= nb_bytes && usable_size < min_size) {
			best = current;
			min_size = usable_size;
		}

		current = current->hdr.next;
	}

	if (best == NULL) {
		PDEBUG("Heap is full");
		return NULL;
	}

	size_t real_size = req2size(nb_bytes); // this is the size of the newly allocated chunk
	PDEBUG("[PicoAlloc]");
	PDEBUG("\t-requested size to alloc: 0x%lx", nb_bytes);
	PDEBUG("\t-real size to alloc: 0x%lx", real_size);

	// update the free list accordingly
	if (best->hdr.size > real_size && best->hdr.size - real_size > MINSIZE) {
    	// first, split the chunk into two chunks if possible
	    size_t remaining_size = best->hdr.size - real_size;
		struct PBChunk* free_chunk = (struct PBChunk*) ((char*) best + real_size);
		best->hdr.size = real_size;

		PDEBUG("remaining_size: 0x%lx", remaining_size);

		free_chunk->hdr.size = remaining_size;
		free_chunk->hdr.next = best->hdr.next;

		if (best == alloc.free_list) {
		    // it is the head of the list, we need to update the global alloc free list
		    alloc.free_list = free_chunk;
		}

		UpdateHash(free_chunk);
	} else {
	    // the free chunk is not being split, we will simply remove it from the free list

		if (alloc.free_list == best) {
		    alloc.free_list = best->hdr.next;
		} else {
		    struct PBChunk* current_free = alloc.free_list;
			while(current_free->hdr.next != best) {
			    current_free = current_free->hdr.next;
			}
			current_free->hdr.next = best->hdr.next;
		}
	}

	// now, append the chunk to allocate.
	alloc_append(best);
	best->hdr.next = NULL;

	UpdateHash(best);

	return CHUNK2DATA(best);
}

// if before and after are next to each other in memory, merge them into one chunk
// return true if the merge happened. it's useful to perform more merges afterwards.
// WARNING: most likely, next chunk ptrs must be updated!
bool TryMergeChunks(struct PBChunk* before, struct PBChunk* after)
{
    if (before == NULL || after == NULL) {
        return false;
    }

    if (NEXTCHUNK(before) == after) {
        before->hdr.size += after->hdr.size;
        UpdateHash(before);

        // should we avoid that to cause funny memory leaks for the challenge?
        RESETCHUNK(after);

        return true;
    }

    return false;
}

void PicoFree(void* data)
{
	struct PBChunk* chunk = DATA2CHUNK(data);
	struct PBChunk* current = NULL;
	PicoAllocCheckChunkAssert(chunk); // chunk should be valid and unmodified

	// remove chunk from the alloc list.
	current = alloc.alloc_list;

	if (current == chunk) {
	    // special case: the first chunk is the free'd chunk
        alloc.alloc_list = current->hdr.next;
	} else {
	    // no need to check for null, the target must be somewhere there.
		// otherwise, it's a bug
	    while(current->hdr.next != chunk) {
			current = current->hdr.next;
		}

		current->hdr.next = chunk->hdr.next;
		UpdateHash(current);
	}

	// now, add the unallocated chunk to the free list
	current = alloc.free_list;

	if (current == NULL) {
	    alloc.free_list = chunk;
		return;
	}

	while(current->hdr.next != NULL) {
	    if (current->hdr.next > chunk) {
			break;
		}

	    current = current->hdr.next;
	}

	if (chunk < current) {
    	// can happen if chunk is smaller than the first block
        if (alloc.free_list != current) {
            PDEBUG("INTERNAL BUG: should never happen!");
            PicoWfe();
        }

        if (TryMergeChunks(chunk, current)) {
            alloc.free_list = chunk;
        } else {
            chunk->hdr.next = current;
            alloc.free_list = chunk;

            UpdateHash(chunk);
        }
	} else {
    	// general case, try to merge neighbours
    	if (TryMergeChunks(current, chunk)) {
            if (current->hdr.next != NULL) {
                struct PBChunk* tmp = current->hdr.next->hdr.next;
           	    if (TryMergeChunks(current, current->hdr.next)) {
                       current->hdr.next = tmp;
                       UpdateHash(current);
                }
            }
    	} else {
    	    if (!TryMergeChunks(chunk, current->hdr.next)) {
    			// all merges failed miserably, just insert the free chunk in the list
    			struct PBChunk* tmp = current->hdr.next;
    			current->hdr.next = chunk;
    			chunk->hdr.next = tmp;

    			UpdateHash(current);
    			UpdateHash(chunk);
    		}
    	}
	}

}

#ifdef PICO_DEBUG
static void PrintChunk(struct PBChunk* chunk)
{
    PPRINTF("Chunk@addr %p", chunk);

    size_t size = chunk->hdr.size;
    size_t hash = chunk->hdr.hash;
    struct PBChunk* next = chunk->hdr.next;
    uintptr_t secret = get_secret(chunk);

    PPRINTF("\t-size      = 0x%lx", size);
    PPRINTF("\t-hash      = 0x%lx", hash);
    PPRINTF("\t-next      = %p", next);
    PPRINTF("\t-secret    = %lx", secret);
    PPRINTF("\t-data      = %p", CHUNK2DATA(chunk));
}
#endif

void PicoAllocPrint()
{
#ifdef PICO_DEBUG
    struct PBChunk* current = NULL;

    PPRINTF("====== Allocator state =====");
    PPRINTF("Allocator start: %p", alloc.mem);
    PPRINTF("Allocator size: 0x%lx", alloc.size);

    PPRINTF("[Alloc list]");
    current = alloc.alloc_list;
    while(current != NULL) {
        PrintChunk(current);
        current = current->hdr.next;
    }

    PPRINTF("[Free list]");
    current = alloc.free_list;
    while(current != NULL) {
        PrintChunk(current);
        current = current->hdr.next;
    }

    PPRINTF("============================");
#endif
}
