#pragma once

#include "PicoCommon.h"
#include "stdio.h"
#include "PicoAlloc.h"

#define NAME_SIZE   32
#define PASSWD_SIZE 128

#define INPUT_PASSWD_SIZE 256

enum PicoBankCmd {
    PICO_BANK_CMD_INVALID        = 0,
	PICO_BANK_CMD_BUY_ADMIN      = 'a',
	PICO_BANK_CMD_SHOW_BANK      = 'b',
    PICO_BANK_CMD_CREATE         = 'c',
	PICO_BANK_CMD_DELETE	     = 'd',
	PICO_BANK_CMD_LOCK		     = 'l',
	PICO_BANK_CMD_GET_MONEY	     = 'm',
    PICO_BANK_CMD_SET_SECRET_KEY = 'k',
    PICO_BANK_CMD_RESET_PASSWD   = 'r',
	PICO_BANK_CMD_SHOW_USER      = 's',
	PICO_BANK_CMD_UNLOCK	     = 'u',
	PICO_BANK_CMD_UNLOCK_BANK	 = 'z',
};

__attribute__((packed)) struct pico_bank_create {
    uint8_t name[NAME_SIZE];
    uint8_t password[PASSWD_SIZE];
};

__attribute__((packed))struct pico_bank_set_note {
    uint8_t* note;
    size_t note_size;
};

__attribute__((packed)) struct pico_bank_reset_passwd {
	uint8_t new_password[PASSWD_SIZE];
	uint8_t new_password_repeated[PASSWD_SIZE];
};

__attribute__((packed)) struct pico_bank_unlock {
	uint8_t password[PASSWD_SIZE];
};

__attribute__((packed)) struct pico_bank_cmd {
    union {
        // PICO_BANK_CMD_CREATE
        struct pico_bank_create create_args;

        // PICO_BANK_CMD_SET_NOTE
        struct pico_bank_set_note set_notes_args;

        // PICO_BANK_RESET_PASSWD
        struct pico_bank_reset_passwd reset_passwd_args;

        // PICO_BANK_UNLOCK
        struct pico_bank_unlock unlock;
    };

    enum PicoBankCmd cmd;
};

void pico_bank_cmd_get(struct pico_bank_cmd* cmd);
