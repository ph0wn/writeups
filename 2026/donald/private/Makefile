CC      := cc
CFLAGS  := -Wall -Wextra -O2
LDFLAGS :=

SRCS := $(wildcard *.c)
BINS := $(SRCS:.c=)

.PHONY: all clean

all: $(BINS)

%: %.c
	$(CC) $(CFLAGS) $< -o $@ $(LDFLAGS)

clean:
	$(RM) $(BINS)

run: all
	./ais_dump | ./ais_server ais.log picomsg.log
