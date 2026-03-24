# Time-lock CTF — C build (replacement for Rust binary for broadcasting)

CC     ?= gcc
CFLAGS ?= -Wall -Wextra -O2 -std=c11
TARGET  = time_lock_ctf

.PHONY: all clean

all: $(TARGET)

$(TARGET): time_lock_ctf.c
	$(CC) $(CFLAGS) -o $(TARGET) time_lock_ctf.c

clean:
	rm -f $(TARGET)
