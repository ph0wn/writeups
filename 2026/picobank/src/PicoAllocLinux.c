#define _GNU_SOURCE
#include <pthread.h>
#include <sys/mman.h>
#include <unistd.h>
#include <stdio.h>
#include <stdlib.h>
#include <stdint.h>
#include <string.h>

#include "PicoAlloc.h"
#include "PicoLinux.h"

static size_t align_up(size_t x, size_t a) { return (x + a - 1) & ~(a - 1); }
static uintptr_t align_down_uintptr(uintptr_t x, size_t a) {
    return x & ~(uintptr_t)(a - 1);
}

struct pico_heap_layout pico_linux_map_heap(size_t alloc_size) {
    struct pico_heap_layout res = {
        .valid = false,
        .start = NULL,
        .len = 0,
    };

    long ps = sysconf(_SC_PAGESIZE);
    if (ps <= 0) {
        perror("sysconf(_SC_PAGESIZE)");
        return res;
    }
    size_t page = (size_t)ps;

    pthread_attr_t attr;
    if (pthread_getattr_np(pthread_self(), &attr) != 0) {
        perror("pthread_getattr_np");
        return res;
    }

    void *stack_addr = NULL;
    size_t stack_size = 0;
    size_t guard_size = 0;

    if (pthread_attr_getstack(&attr, &stack_addr, &stack_size) != 0) {
        perror("pthread_attr_getstack");
        pthread_attr_destroy(&attr);
        return res;
    }
    if (pthread_attr_getguardsize(&attr, &guard_size) != 0) {
        perror("pthread_attr_getguardsize");
        pthread_attr_destroy(&attr);
        return res;
    }
    pthread_attr_destroy(&attr);

    uintptr_t stack_base = align_down_uintptr((uintptr_t)stack_addr, page);

    size_t guard_len = guard_size ? align_up(guard_size, page) : page;
    uintptr_t guard_start = stack_base - guard_len;

    size_t want_alloc = alloc_size;
    size_t alloc_len  = align_up(want_alloc, page);
    uintptr_t alloc_start = stack_base - alloc_len;

#ifdef MADV_GUARD_REMOVE
    (void)madvise((void*)guard_start, guard_len, MADV_GUARD_REMOVE);
#endif

    if (munmap((void*)guard_start, guard_len) != 0) {
        perror("munmap(guard)");
        return res;
    }

    void *alloc = mmap((void*)alloc_start, alloc_len,
                       PROT_READ | PROT_WRITE,
                       MAP_PRIVATE | MAP_ANONYMOUS | MAP_FIXED, // with sanitizers, there will be an additional page mapped there supposed to check for overflows.
                       -1, 0);

    if (alloc == MAP_FAILED) {
        perror("mmap(allocator)");
        fprintf(stderr,
                "Failed to map [%p, %p)\n",
                (void*)alloc_start, (void*)stack_base);
        return res;
    }

    memset(alloc, 0, alloc_len);

    PRINTF("[allocator layout]\n");
    PRINTF("  stack_base = %p\n", (void*)stack_base);
    PRINTF("  allocator_base = %p\n", alloc);
    PRINTF("  allocator_len = %zu\n", alloc_len);
    PRINTF("  allocator_end = %p\n", (void*)(alloc_start + alloc_len));
    PRINTF("  adjacent? = %s\n",
           ((alloc_start + alloc_len) == stack_base) ? "YES" : "NO");

    res.start = (void*) alloc_start;
    res.len = alloc_len;
    res.valid = true;

    return res;
}
