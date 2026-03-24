#include <string.h>
#include <stdio.h>
#include <stdlib.h>

#include "PicoCommon.h"

#include "PicoCmd.h"
#include "PicoAlloc.h"

// safe: it will ALWAYS end with \0, and NEVER go over max_len (last \0 included!)
static size_t GetLine(uint8_t* dst, size_t max_len)
{
	size_t len = 0;
	int c = 0;

	if (max_len == 0) {
		return 0;
	}

	while((c = GETCHAR()) != '\n' && len < max_len - 1) {
		if (c < 0) {
			PPRINTF("Error while getting input...");
			PicoWfe();
		}

		*(dst + len++) = (char) c;
	}

	dst[len] = '\0';

	return len;
}

static enum PicoBankCmd get_command() {
    PPRINTF("");
	PRINTF("Command: ");

    int cmd = GETCHAR();
    if (cmd < 0) {
        PPRINTF("Error while fetching command...");
        return PICO_BANK_CMD_INVALID;
    }

    PPRINTF("");

    return (enum PicoBankCmd) cmd;
}

bool parse_create(struct pico_bank_create* create)
{
    PRINTF("Name: ");
    GetLine(create->name, sizeof(create->name));
    PPRINTF("");

    PRINTF("Password: ");
    GetLine(create->password, sizeof(create->password));
    PPRINTF("");

    return true;
}

bool parse_set_note(struct pico_bank_set_note* notes)
{
    PRINTF("Secret key size: ");
    uint8_t input[32] = {0};
    GetLine(input, sizeof(input));
    PPRINTF("");

    unsigned long long notes_size = strtoull((char*) input, NULL, 0);

    if (notes_size == 0) {
        PPRINTF("Error: invalid size");
        return false;
    }

    PDEBUG("Trying to allocate a secret key of size 0x%lx", notes_size);

    notes->note = PicoMalloc(notes_size + 1);

    if (notes->note == NULL) {
        PPRINTF("ERROR: Allocation failed.");
        return false;
    }

    PRINTF("Secret key content: ");
    GetLine(notes->note, notes_size + 1);
    PPRINTF("");

    return true;
}

bool parse_reset_passwd(struct pico_bank_reset_passwd* args)
{
	// zero the password memory
	// it's to avoid lucky solves mostly
    memset(args->new_password, 0, sizeof(args->new_password));
	memset(args->new_password_repeated, 0, sizeof(args->new_password_repeated));

    PRINTF("New password: ");
    GetLine(args->new_password, sizeof(args->new_password));
    PPRINTF("");

    PRINTF("Repeat new password: ");
    GetLine(args->new_password_repeated, sizeof(args->new_password_repeated));
    PPRINTF("");

    return true;
}


bool parse_unlock(struct pico_bank_unlock* args)
{
	// zero the password memory
	// it's to avoid lucky solves mostly
    memset(args->password, 0, sizeof(args->password));

    PRINTF("Account password: ");
    GetLine(args->password, sizeof(args->password));
    PPRINTF("");

    return true;
}

void pico_bank_cmd_get(struct pico_bank_cmd* cmd)
{
    cmd->cmd = get_command();

    bool success = false;

    switch (cmd->cmd) {
        case PICO_BANK_CMD_CREATE: {
            PDEBUG("[*] Create new bank account");
            success = parse_create(&cmd->create_args);
            break;
        }
        case PICO_BANK_CMD_SHOW_USER: {
            PDEBUG("[*] Show account");
            success = true;
            break;
        }
        case PICO_BANK_CMD_SHOW_BANK: {
            PDEBUG("[*] Show account");
            success = true;
            break;
        }
        case PICO_BANK_CMD_GET_MONEY: {
        	success = true;
        	break;
        }
        case PICO_BANK_CMD_DELETE: {
        	success = true;
        	break;
        }
        case PICO_BANK_CMD_BUY_ADMIN: {
        	success = true;
        	break;
        }
        case PICO_BANK_CMD_SET_SECRET_KEY: {
            PDEBUG("[*] Set bank account private key");
            success = parse_set_note(&cmd->set_notes_args);
            PicoAllocPrint();
            break;
        }
        case PICO_BANK_CMD_RESET_PASSWD: {
            PDEBUG("[*] Reset bank account password");
            success = parse_reset_passwd(&cmd->reset_passwd_args);
            break;
        }
        case PICO_BANK_CMD_LOCK: {
        	success = true;
        	break;
        }
        case PICO_BANK_CMD_UNLOCK: {
        	success = parse_unlock(&cmd->unlock);
        	break;
        }
        case PICO_BANK_CMD_UNLOCK_BANK: {
        	success = true;
        	break;
        }
        default: {
            PDEBUG("[!] Unknown command: %d", cmd->cmd);
            break;
        }
    }

    if (!success) {
        cmd->cmd = PICO_BANK_CMD_INVALID;
    }
}
