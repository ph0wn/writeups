#ifndef PICOBANKMANAGER_H_
#define PICOBANKMANAGER_H_

#include "stdint.h"
#include "PicoCmd.h"

void PicoBankManagerInit();

bool PicoBankCreate(struct pico_bank_create* cmd);
bool PicoBankSetNote(struct pico_bank_set_note* notes);
bool PicoBankResetPasswd(struct pico_bank_reset_passwd* args);
bool PicoBankGetMoney(void);
bool PicoBankDelete(void);
bool PicoBankBuyAdmin(void);
bool PicoBankUnlock(struct pico_bank_unlock* args);
bool PicoBankLock(void);
bool PicoBankUnlockAdminVault(void);
bool PicoBankShowUser(void);
bool PicoBankShowBank(void);


#endif /* PICOBANKMANAGER_H_ */
