start:

	lui s0,0x20000		# flag is at 0x20000000
	li s1,0			# count, unused
	lui s2,0x08001
	addi s2,s2,0x0ee	# 080010ee = 0x08001<<12 + 0x0ee --> put_char(c)
loop:
	lbu a0,0(s0)		# get one byte
	jalr s2			# call put_char()
	addi s0,s0,1		# next char
	j loop			# do it again

