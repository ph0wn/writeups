/*
 * Adapted for Ph0wn CTF challenge from 
 * The ARM64 Exploit Laboratory
 * by Saumil Shah
 *
 * gcc trail.c -o trail
 */

#include <stdio.h>
#include <stdlib.h>

void register_runner();

int main()
{
  printf("---------- ==== PH0WN ULTRA TRAIL ==== ---------\n");
  printf("               ~ Only 1337 kms ~\n");
  printf("               REGISTRATION SERVER\n");
  fflush(stdout);
  register_runner();
  printf("Good luck!\n");
}

void register_runner()
{
   char name[30];

   printf("Runner name: ");
   fflush(stdout);
   gets(name);
   printf("%s, you have successfully been registered\n", name);
}
