#include <stdio.h>
#include <string.h>

#ifndef PICO_LINUX
#include "board.h"
#include "peripherals.h"
#include "pin_mux.h"
#include "clock_config.h"
#include "fsl_debug_console.h"
#endif

#include "PicoCommon.h"
#include "PicoBankManager.h"
#include "PicoCmd.h"
#include "PicoRandom.h"

#define _STRINGIFY(A) #A
#define STRINGIFY(A) _STRINGIFY(A)

#define ADMIN_COOKIE 0xC4F3B4B3

#define MONEY_INCREMENT 5
#define MONEY_LIMIT     2000
#define MONEY_BUY_ADMIN 99999

#define NOTE_TEXT_WIDTH     24
#define MONEY_DISPLAY_WIDTH 20
#define NAME_WIDTH          61

#define BIG_PADDING_SIZE 508 // to have a good struct for collision
#define SMALL_PADDING_SIZE (16 * 26) // to have a good struct for collision

struct __attribute__((packed)) PicoBankAccount {
    char name[32];
    uint8_t* note;
    size_t note_size;
    uint32_t money;
    bool locked;
    char password[PASSWD_SIZE];
    char big_padding[BIG_PADDING_SIZE]; // this is to avoid the stack hitting the header with the smashing check
    uint32_t cookie;
    char small_padding[SMALL_PADDING_SIZE - 1]; // this is to calibrate the cookie so that it's reachable by the password change!
};

struct PicoBankState {
    struct PicoBankAccount* account;
};

struct PicoBankState bs;

struct PicoBankAccount bank;
uint8_t flag[128];

static bool PicoAllowed(void) {
	if (!bs.account) {
		PPRINTF("No account found.");
		return false;
	}

	if (bs.account->locked) {
		PPRINTF("Account locked.");
		return false;
	}

	return true;
}

static bool PicoBankAdminAccess(struct PicoBankAccount* account)
{
    if (account == NULL) {
        PPRINTF("Error: no account has been opened.");
        return false;
    }

    if (account->cookie == ADMIN_COOKIE) {
        return true;
    } else {
        return false;
    }
}

void PicoBankManagerInit()
{
	PPRINTF("[PicoBank App] Initializing...");
	PPRINTF("\tVersion: 0.0.2");

	bs.account = NULL;

	bank.money = 99999999;
	const char name[] = "PicoBank Administrators";
	memcpy(bank.name, name, sizeof(name));

	const char local_flag[] = STRINGIFY(FLAG);
	memcpy(flag, local_flag, sizeof(local_flag));

	bank.note = flag;
	bank.note_size = sizeof(flag);
	bank.locked = true;


	PRINTF("[PicoBank App] Initialization done.");
	PPRINTF("");
}

bool PicoBankCreate(struct pico_bank_create* cmd)
{
	if (bs.account) {
	    PPRINTF("Account already created.");
	    return false;
	}

    struct PicoBankAccount* account = PicoMalloc(sizeof(struct PicoBankAccount));

    if (!account) {
    	PPRINTF("Unexpected error while creating new account...");
        return false;
    }

    memcpy(account->name, cmd->name, sizeof(account->name));
    memcpy(account->password, cmd->password, sizeof(account->password));
    account->note = NULL;

    account->cookie = 0;
    account->money = 0;
    account->locked = true;

    bs.account = account;
    PPRINTF("New account created successfully.");

    return true;
}

bool PicoBankGetMoney()
{
	if (!PicoAllowed()) {
		return false;
	}

	uintptr_t try_money = PicoRandomGet();

	if (try_money % 4 == 0) {
		PPRINTF("It's your lucky day! I can give you some pennies...");
		bs.account->money += MONEY_INCREMENT;

		if (bs.account->money > MONEY_LIMIT) {
			PPRINTF("Did you think you could steal me forever? I'll empty your account in compensation.");
			bs.account->money = 0;
		}
	} else {
		PPRINTF("PicoBank is not a charity!");
	}

	return true;
}

bool PicoBankDelete()
{
	if (!PicoAllowed()) {
		return false;
	}

	if (bs.account->note) {
		PicoFree(bs.account->note);
		bs.account->note = NULL;
	}

	PicoFree(bs.account);
	bs.account = NULL;

	PPRINTF("Account deleted successfully.");

	return true;
}

bool PicoBankBuyAdmin()
{
	if (!PicoAllowed()) {
		return false;
	}

	if (bs.account->money >= MONEY_BUY_ADMIN) {
		PPRINTF("You are rich now, I will promote you to a PicoBank Administrator.");
		bs.account->money -= MONEY_BUY_ADMIN;
		bs.account->cookie = ADMIN_COOKIE;
	} else {
		PPRINTF("Poor people cannot join the PicoBank Administrators!");
	}

	return true;
}


bool PicoBankSetNote(struct pico_bank_set_note* notes)
{
	if (!PicoAllowed()) {
		return false;
	}

    bs.account->note = notes->note;
    bs.account->note_size = notes->note_size;
    bs.account->note[notes->note_size - 1] = '\0';

    // just in case
    notes->note = NULL;
    notes->note_size = 0;

    return true;
}

bool PicoBankResetPasswd(struct pico_bank_reset_passwd* args)
{
	if (!PicoAllowed()) {
		return false;
	}

    if (strcmp((char*) args->new_password, (char*) args->new_password_repeated)) {
    	PPRINTF("New passwords differ.");
    	return false;
    }

    memcpy(bs.account->password, args->new_password, sizeof(bs.account->password));
    bs.account->password[PASSWD_SIZE - 1] = '\0';

    PPRINTF("Password has been reset successfully.");

    return true;
}

bool PicoBankUnlock(struct pico_bank_unlock* args)
{
	if (!bs.account) {
		PPRINTF("No account found.");
		return false;
	}

    if (strcmp((char*) args->password, (char*) bs.account->password)) {
    	PPRINTF("Unlock denied.");
    	return false;
    }

    bs.account->locked = false;
    PPRINTF("Account unlocked successfully.");

	return true;
}

bool PicoBankLock()
{
	if (!bs.account) {
		PPRINTF("No account found.");
		return false;
	}

	bs.account->locked = true;
    PPRINTF("Account locked successfully.");

	return true;
}

bool PicoBankUnlockAdminVault()
{
	if (!PicoAllowed()) {
		return false;
	}

    if (PicoBankAdminAccess(bs.account)) {
    	PPRINTF("Administrator safe unlocked successfully.");
    	bank.locked = false;
    	return true;
    } else {
    	PPRINTF("Administrator safe unlock denied.");
    	return false;
    }
}

static void drawSafeClosed(const char *name)
{
    char buf[128];

    char nameBuf[NAME_WIDTH + 1];
    int nLen = strlen(name);
    if (nLen > NAME_WIDTH) nLen = NAME_WIDTH;
    memset(nameBuf, ' ', NAME_WIDTH);
    nameBuf[NAME_WIDTH] = '\0';
    int nPad = (NAME_WIDTH - nLen) / 2;
    memcpy(nameBuf + nPad, name, nLen);

    PPRINTF("    ._______________________________________________________________.");
    snprintf(buf, sizeof(buf), "    |  %s|", nameBuf);
    PPRINTF("%s", buf);
    PPRINTF("    |  (o)  ._______________________________________________.  (o)  |");
    PPRINTF("    |       |                                                |      |");
    PPRINTF("    |       |         .---------.                            |      |");
    PPRINTF("    |       |        /     |     \\                           |      |");
    PPRINTF("    |       |       /      |      \\                          |      |");
    PPRINTF("    |       |      |  -----+-----  |      |========|         |      |");
    PPRINTF("    |       |       \\      |      /       |        |         |      |");
    PPRINTF("    |       |        \\     |     /        |        |         |      |");
    PPRINTF("    |       |         '---------'         |========|         |      |");
    PPRINTF("    |       |                                                |      |");
    PPRINTF("    |       |                                                |      |");
    PPRINTF("    |       |________________________________________________|      |");
    PPRINTF("    |  (o)                                                    (o)   |");
    PPRINTF("    |_______________________________________________________________|");
    PPRINTF("    [________]                                            [________]");
}

static void drawSafeEmpty(const char *name)
{
    char buf[128];

    char nameBuf[NAME_WIDTH + 1];
    int nLen = strlen(name);
    if (nLen > NAME_WIDTH) nLen = NAME_WIDTH;
    memset(nameBuf, ' ', NAME_WIDTH);
    nameBuf[NAME_WIDTH] = '\0';
    int nPad = (NAME_WIDTH - nLen) / 2;
    memcpy(nameBuf + nPad, name, nLen);

    PPRINTF("    ._______________________________________________________________._________________________.");
    snprintf(buf, sizeof(buf), "    |  %s|                          |", nameBuf);
    PPRINTF("%s", buf);
    PPRINTF("    |  (o)  ._______________________________________________.  (o)  |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |________________________________________________|      |                          |");
    PPRINTF("    |  (o)                                                     (o)  |                          |");
    PPRINTF("    |_______________________________________________________________|__________________________|");
    PPRINTF("    [________]                                            [________]");
}

static void drawSafeMoney(const char *name, size_t money)
{
    char buf[128];

    char nameBuf[NAME_WIDTH + 1];
    int nLen = strlen(name);
    if (nLen > NAME_WIDTH) nLen = NAME_WIDTH;
    memset(nameBuf, ' ', NAME_WIDTH);
    nameBuf[NAME_WIDTH] = '\0';
    int nPad = (NAME_WIDTH - nLen) / 2;
    memcpy(nameBuf + nPad, name, nLen);

    char moneyStr[MONEY_DISPLAY_WIDTH + 1];
    char rawMoney[32];
    snprintf(rawMoney, sizeof(rawMoney), "$ %u", (unsigned int)money);
    int mLen = strlen(rawMoney);
    if (mLen > MONEY_DISPLAY_WIDTH) mLen = MONEY_DISPLAY_WIDTH;
    memset(moneyStr, ' ', MONEY_DISPLAY_WIDTH);
    moneyStr[MONEY_DISPLAY_WIDTH] = '\0';
    int mPad = (MONEY_DISPLAY_WIDTH - mLen) / 2;
    memcpy(moneyStr + mPad, rawMoney, mLen);

    PPRINTF("    ._______________________________________________________________._________________________.");
    snprintf(buf, sizeof(buf), "    |  %s|                          |", nameBuf);
    PPRINTF("%s", buf);
    PPRINTF("    |  (o)  ._______________________________________________.  (o)  |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |        .--------------------.                  |      |                          |");
    snprintf(buf, sizeof(buf), "    |       |        |%s|                  |      |                          |", moneyStr);
    PPRINTF("%s", buf);
    PPRINTF("    |       |        |____________________|                  |      |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |           ($)  ($)  ($)  ($)  ($)  ($)         |      |                          |");
    PPRINTF("    |       |            ($)  ($)  ($)  ($)  ($)             |      |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |________________________________________________|      |                          |");
    PPRINTF("    |  (o)                                                     (o)  |                          |");
    PPRINTF("    |_______________________________________________________________|__________________________|");
    PPRINTF("    [________]                                            [________]");
}

static void drawSafeSecretKey(const unsigned char *secret_key, const char *name)
{
    char buf[128];

    char nameBuf[NAME_WIDTH + 1];
    int nLen = strlen(name);
    if (nLen > NAME_WIDTH) nLen = NAME_WIDTH;
    memset(nameBuf, ' ', NAME_WIDTH);
    nameBuf[NAME_WIDTH] = '\0';
    int nPad = (NAME_WIDTH - nLen) / 2;
    memcpy(nameBuf + nPad, name, nLen);

    int len = strlen((const char *)secret_key);
    int l1 = (len > NOTE_TEXT_WIDTH) ? NOTE_TEXT_WIDTH : len;
    int l2 = (len > NOTE_TEXT_WIDTH)
           ? ((len - NOTE_TEXT_WIDTH > NOTE_TEXT_WIDTH) ? NOTE_TEXT_WIDTH : len - NOTE_TEXT_WIDTH)
           : 0;

    char txt1[NOTE_TEXT_WIDTH + 1];
    char txt2[NOTE_TEXT_WIDTH + 1];
    memset(txt1, ' ', NOTE_TEXT_WIDTH);
    memset(txt2, ' ', NOTE_TEXT_WIDTH);
    txt1[NOTE_TEXT_WIDTH] = '\0';
    txt2[NOTE_TEXT_WIDTH] = '\0';
    memcpy(txt1, secret_key, l1);
    if (l2 > 0)
        memcpy(txt2, secret_key + l1, l2);

    PPRINTF("    ._______________________________________________________________._________________________.");
    snprintf(buf, sizeof(buf), "    |  %s|                          |", nameBuf);
    PPRINTF("%s", buf);
    PPRINTF("    |  (o)  ._______________________________________________.  (o)  |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |     .----------------------------.             |      |                          |");
    PPRINTF("    |       |     |   ** CONFIDENTIAL **        |            |      |                          |");
    snprintf(buf, sizeof(buf), "    |       |     |   %s  |            |      |                          |", txt1);
    PPRINTF("%s", buf);
    snprintf(buf, sizeof(buf), "    |       |     |   %s  |            |      |                          |", txt2);
    PPRINTF("%s", buf);
    PPRINTF("    |       |     |____________________________|             |      |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |________________________________________________|      |                          |");
    PPRINTF("    |  (o)                                                     (o)  |                          |");
    PPRINTF("    |_______________________________________________________________|__________________________|");
    PPRINTF("    [________]                                            [________]");
}

static void drawSafeSecretKeyMoney(const unsigned char *secret_key, const char *name, size_t money)
{
    char buf[128];

    /* Owner name - centered */
    char nameBuf[NAME_WIDTH + 1];
    int nLen = strlen(name);
    if (nLen > NAME_WIDTH) nLen = NAME_WIDTH;
    memset(nameBuf, ' ', NAME_WIDTH);
    nameBuf[NAME_WIDTH] = '\0';
    int nPad = (NAME_WIDTH - nLen) / 2;
    memcpy(nameBuf + nPad, name, nLen);

    /* Secret key text */
    int len = strlen((const char *)secret_key);
    int l1 = (len > NOTE_TEXT_WIDTH) ? NOTE_TEXT_WIDTH : len;
    int l2 = (len > NOTE_TEXT_WIDTH)
           ? ((len - NOTE_TEXT_WIDTH > NOTE_TEXT_WIDTH) ? NOTE_TEXT_WIDTH : len - NOTE_TEXT_WIDTH)
           : 0;

    char txt1[NOTE_TEXT_WIDTH + 1];
    char txt2[NOTE_TEXT_WIDTH + 1];
    memset(txt1, ' ', NOTE_TEXT_WIDTH);
    memset(txt2, ' ', NOTE_TEXT_WIDTH);
    txt1[NOTE_TEXT_WIDTH] = '\0';
    txt2[NOTE_TEXT_WIDTH] = '\0';
    memcpy(txt1, secret_key, l1);
    if (l2 > 0)
        memcpy(txt2, secret_key + l1, l2);

    /* Money amount - centered in bill */
    char moneyStr[MONEY_DISPLAY_WIDTH + 1];
    char rawMoney[32];
    snprintf(rawMoney, sizeof(rawMoney), "$ %u", (unsigned int)money);
    int mLen = strlen(rawMoney);
    if (mLen > MONEY_DISPLAY_WIDTH) mLen = MONEY_DISPLAY_WIDTH;
    memset(moneyStr, ' ', MONEY_DISPLAY_WIDTH);
    moneyStr[MONEY_DISPLAY_WIDTH] = '\0';
    int mPad = (MONEY_DISPLAY_WIDTH - mLen) / 2;
    memcpy(moneyStr + mPad, rawMoney, mLen);

    /* Draw */
    PPRINTF("    ._______________________________________________________________._________________________.");
    snprintf(buf, sizeof(buf), "    |  %s|                          |", nameBuf);
    PPRINTF("%s", buf);
    PPRINTF("    |  (o)  ._______________________________________________.  (o)  |                          |");
    PPRINTF("    |       |                                                |      |                          |");
    PPRINTF("    |       |     .----------------------------.             |      |                          |");
    PPRINTF("    |       |     |   ** CONFIDENTIAL **        |            |      |                          |");
    snprintf(buf, sizeof(buf), "    |       |     |   %s  |            |      |                          |", txt1);
    PPRINTF("%s", buf);
    snprintf(buf, sizeof(buf), "    |       |     |   %s  |            |      |                          |", txt2);
    PPRINTF("%s", buf);
    PPRINTF("    |       |     |____________________________|             |      |                          |");
    PPRINTF("    |       |        .--------------------.                  |      |                          |");
    snprintf(buf, sizeof(buf), "    |       |        |%s|                  |      |                          |", moneyStr);
    PPRINTF("%s", buf);
    PPRINTF("    |       |        |____________________|                  |      |                          |");
    PPRINTF("    |       |           ($)  ($)  ($)  ($)  ($)  ($)         |      |                          |");
    PPRINTF("    |       |________________________________________________|      |                          |");
    PPRINTF("    |  (o)                                                     (o)  |                          |");
    PPRINTF("    |_______________________________________________________________|__________________________|");
    PPRINTF("    [________]                                            [________]");
}
void drawSafe(struct PicoBankAccount* acc)
{
	if (acc->locked) {
		drawSafeClosed(acc->name);
		return;
	}

	if (acc->money > 0) {
		if (acc->note) {
			drawSafeSecretKeyMoney(acc->note, acc->name, acc->money);
		} else {
			drawSafeMoney(acc->name, acc->money);
		}
	} else {
		if (acc->note) {
			drawSafeSecretKey(acc->note, acc->name);
		} else {
			drawSafeEmpty(acc->name);
		}
	}
}

bool PicoBankShowBank()
{
	if (!bs.account) {
		PPRINTF("No account found.");
		return false;
	}

    drawSafe(&bank);
    return true;
}

bool PicoBankShowUser()
{
	if (!bs.account) {
		PPRINTF("No account found.");
		return false;
	}

    drawSafe(bs.account);
    return true;
}
