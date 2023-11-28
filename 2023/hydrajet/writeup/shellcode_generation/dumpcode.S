.thumb

start:
    sub sp, #0x50		// protect the current code from interruption
    mov r2, #0x80		// TXE bit
    ldr r0, flash_adr		// @ to dump
    ldr r1, uart_base_adr	// uart1 base @

.send_loop:
    ldrb r3, [r0]		// load flash value from r0 into r3
    add r0, #1			// next byte

    strb r3, [r1, #7]		// send current byte  USAR_DR

.wait_tx:
    ldr  r3, [r1, #0]		// get status register  USAR_SR
    and  r3, r3, r2		// keep only TXE bit
    beq  .wait_tx		// tx fifo empty?
    b .send_loop		// infinite loop to next byte


    nop				// 2 bytes lost to align the 2 @ bellow on 32 bits memory position
flash_adr:
.word 0x0804a904

uart_base_adr:
.word 0x40011000

