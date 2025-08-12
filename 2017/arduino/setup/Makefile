OBJECTS = password.o

all: password

%.o: %.c
	gcc -c $< -o $@	

password: $(OBJECTS)
	gcc $(OBJECTS) -o $@	

clean:
	-rm -f $(OBJECTS)
	-rm -f password
