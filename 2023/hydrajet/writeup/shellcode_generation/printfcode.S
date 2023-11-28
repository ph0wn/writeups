.thumb

start:
    sub sp, #0x48		// add exacly the same value as FUN_0802c270 (push + 0x44 = 0x48)
    ldr r0, printf_adr		// load the @ of the printf('flag') deadcode
    bx r0			// jump to the printf

    nop 			// 2 bytes lost to align the @ bellow on 32 bits memory position
printf_adr:
.word 0x0802c2d9

