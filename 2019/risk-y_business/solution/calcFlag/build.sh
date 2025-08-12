gcc -c -o sha3.o sha3.c
gcc -c -o run.o run.c
gcc sha3.o run.o -o run
