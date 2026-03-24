#include "PicoAlloc.h"
#ifdef PICO_LINUX
#include <pthread.h>
#include <stdio.h>
#else
#include "board.h"
#include "peripherals.h"
#include "pin_mux.h"
#include "clock_config.h"
#include "fsl_debug_console.h"
#endif

#include "PicoCmd.h"
#include "PicoBankManager.h"
#include "PicoCommon.h"
#include "PicoRandom.h"

#ifdef PICO_DEBUG
#include <PicoAllocTests.h>
#endif

void PicoWfe() {
	PDEBUG("Fatal error");

    while(1) {
        __asm volatile ("nop");
    }
}

static void bank_app();

static void handle_bank_app_error(const char* err)
{
    PPRINTF("Error while executing banking app: %s", err);

    bank_app();
}

//static void print_help(void)
//{
//	PPRINTF("Welcome to PicoBank, the safest bank in the world!");
//	PPRINTF("We only handle two safes: the client's safe and the bank safe.");
//	PPRINTF("You can freely get access to a client safe, but the bank safe can only be opened by an administrator.");
//	PPRINTF("The bank safe contains the most precious treasures of Pico, and can only be accessed by trusted people.");
//}

static void bank_app()
{
    struct pico_bank_cmd cmd = {};

	while(true) {
        PDEBUG("password @addr %p -> %p",
        		&cmd.reset_passwd_args.new_password,
				&cmd.reset_passwd_args.new_password + 1);
        pico_bank_cmd_get(&cmd);

        PicoAllocCheckAssert();

        bool success = false;
        switch (cmd.cmd) {
            case PICO_BANK_CMD_CREATE: {
                success = PicoBankCreate(&cmd.create_args);
                break;
            }
            case PICO_BANK_CMD_SHOW_USER: {
                success = PicoBankShowUser();
                break;
            }
            case PICO_BANK_CMD_SHOW_BANK: {
                success = PicoBankShowBank();
                break;
            }
            case PICO_BANK_CMD_SET_SECRET_KEY: {
                // intentionally do not free the allocated memory by command parser here
                // it is the intended way to get the flag
                success = PicoBankSetNote(&cmd.set_notes_args);
                break;
            }
            case PICO_BANK_CMD_RESET_PASSWD: {
                success = PicoBankResetPasswd(&cmd.reset_passwd_args);
                break;
            }
            case PICO_BANK_CMD_DELETE: {
            	success = PicoBankDelete();
            	break;
            }
            case PICO_BANK_CMD_GET_MONEY: {
            	success = PicoBankGetMoney();
            	break;
            }
            case PICO_BANK_CMD_BUY_ADMIN: {
            	success = PicoBankBuyAdmin();
            	break;
            }
            case PICO_BANK_CMD_LOCK: {
            	success = PicoBankLock();
            	break;
            }
            case PICO_BANK_CMD_UNLOCK: {
            	success = PicoBankUnlock(&cmd.unlock);
            	break;
            }
            case PICO_BANK_CMD_UNLOCK_BANK: {
            	success = PicoBankUnlockAdminVault();
            	break;
            }
            default: {
            	success = false;
            	break;
            }
        }

        if (!success) {
            handle_bank_app_error("command failed");
        }
    }
}

static void* thread_main(void* arg)
{
    (void)arg;

    setvbuf(stdin, NULL, _IONBF, 0);

    PPRINTF("");
    PicoRandomInit();
    PicoAllocInit();
    PicoBankManagerInit();

#if defined(PICO_DEBUG) && defined(PICO_LINUX)
    pico_alloc_all_tests();
#else
    bank_app();
#endif

    /* Force the counter to be placed into memory. */
    volatile static int i = 0 ;
    /* Enter an infinite loop, just incrementing a counter. */
    while(1) {
        i++ ;
        /* 'Dummy' NOP to allow source level single stepping of
            tight while() loop */
        __asm__ volatile ("nop");
    }
    // ... run your allocator/server here ...
    // server();

    return NULL;
}

/*
 * @brief   Application entry point.
 */
int main(void) {
#ifdef PICO_LINUX
    pthread_t tid;
    if (pthread_create(&tid, NULL, thread_main, NULL) != 0) {
        perror("pthread_create");
        return 1;
    }

    pthread_join(tid, NULL);

    return 0;
#else
    /* Init board hardware. */
    BOARD_InitBootPins();
    BOARD_InitBootClocks();
    BOARD_InitBootPeripherals();
    /* Init FSL debug console. */
    BOARD_InitDebugConsole();

    thread_main(NULL);
#endif

    return 0 ;
}
