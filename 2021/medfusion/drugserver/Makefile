CC = gcc
#CFLAGS = -DDEBUG -w -g
CFLAGS =
DRUGSERVER_LIBS = -lpthread -no-pie
EXECUTABLES = drugserver public_drugserver
all: $(EXECUTABLES)

drugserver: drugserver.o
	$(CC) $^ $(DRUGSERVER_LIBS) -o $@

public_drugserver: public_drugserver.o
	$(CC) $^ $(DRUGSERVER_LIBS) -o $@

public_drugserver.c: drugserver.c
	sed -e 's/0d1b344e5b{152c4e50222c4f601b2c4f5b182c3f591837}/id{this_is_a_fake_therapy_id____absolutely_fake}/g' $^ > $@

%.o : %.c
	$(CC) $(CFLAGS) $< -c

clean:
	rm -f *.o $(EXECUTABLES) public_drugserver.c
