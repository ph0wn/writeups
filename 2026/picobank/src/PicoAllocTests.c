#include "PicoAlloc.h"
#include "PicoLinux.h"

#include "PicoAllocInternals.h"

static void report_error(const char* err)
{
    PRINTF("Error: %s\n", err);
}

static void test_limits()
{
    char* buf = PicoMalloc(PICO_HEAP_SIZE - MINSIZE);
    PicoAllocPrint();
    PicoAllocCheckAssert();
    PicoFree(buf);
    PicoAllocPrint();
    PicoAllocCheckAssert();
}

static void test_signature()
{
    PRINTF("Allocating arrays...");
    char* array = PicoMalloc(16 * sizeof(char));
    char* next_chunk = PicoMalloc(4 * sizeof(char));
    PRINTF("Done.\n");

    PicoAllocPrint();

    // this is an intentional overflow
    char tmp = array[16];

    for (int i = 0; i < 17; ++i) {
        PRINTF("Setting array[%d]...\n", i);
        array[i] = 'a' + i;
    }

    if (PicoAllocCheck()) {
        report_error("hash check should fail\n");
        return;
    } else {
        PRINTF("\t-> error have been detected as expected\n");
    }

    // restore tmp to get correct free
    array[16] = tmp;

    PicoAllocPrint();
    PRINTF("Freeing array...\n");
    PicoFree(array);

    PicoAllocPrint();
    PRINTF("Freeing next chunk...\n");
    PicoFree(next_chunk);
    PicoAllocPrint();
    PicoAllocCheckAssert();
}

static void test_simple()
{
    PRINTF("Allocating array...\n");
    char* array = PicoMalloc(16 * sizeof(char));
    PRINTF("Done.\n");

    PicoAllocPrint();

    for (int i = 0; i < 16; ++i) {
        PRINTF("Setting array[%d]...\n", i);
        array[i] = 'a' + i;
    }

    for (int i = 0; i < 16; ++i) {
        PRINTF("array[%d] = %c\n", i, array[i]);

        if (array[i] != 'a' + i) {
            report_error("incorrect value");
        }
    }

    PicoFree(array);

    PicoAllocPrint();

    PicoAllocCheckAssert();
}

void pico_alloc_all_tests()
{
    PRINTF("Running all pico alloc tests...\n");

    PicoAllocInit();
    PicoAllocPrint();

    PRINTF("Test simple\n");
    test_simple();
    PRINTF("Test simple finished.\n");

    PRINTF("Test signature check\n");
    test_signature();
    PRINTF("Test signature finished.\n");

    PRINTF("Test limits\n");
    test_limits();
    PRINTF("Test limits finished.\n");

    PRINTF("All tests finished successfully\n");
}
