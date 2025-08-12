#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <termios.h>
#include <unistd.h>
#include <sys/mman.h>

#include "button.h"
#include "gpio.h"
#include "lcd.h"
#include "lcd_cgram.h"

#include "sha256.c"

#define PAGE_SIZE 4096

FILE *file;
int  logged=0;
int  armed=1;
char hashBuffer[32];
char currentPasswd[32];
char rawBuffer[667];
char flagDisarmed[30];

void readPasswd()
{
	memset(rawBuffer,0,666);
	file = fopen("pwd.ini", "r");
	fgets(rawBuffer, 666, file);
	if (ferror(file))
		exit(1);
	fclose(file);
	hash2(rawBuffer,strlen(rawBuffer),currentPasswd);
	memset(rawBuffer,0,666);
}

void readFlag()
{
	memset(flagDisarmed,0,30);
	file = fopen("flagDisarmed.ini", "r");
	fgets(flagDisarmed, 29, file);
	if (ferror(file))
		exit(1);
	fclose(file);
}

void display()
{
	LCD_clear();
	LCD_home();

	LCD_putchar('O');
	LCD_putchar('\n');
	LCD_putchar('K');
}

void arm()
{
	armed=1;
	LCD_colour(Red);
	LCD_clear();
	LCD_home();
	LCD_putchar('*');
	LCD_putchar('*');
	LCD_putchar('*');
	LCD_putchar(' ');
	LCD_putchar('A');
	LCD_putchar('l');
	LCD_putchar(' ');
	LCD_putchar('B');
	LCD_putchar('e');
	LCD_putchar('r');
	LCD_putchar('r');
	LCD_putchar('y');
	LCD_putchar(' ');
	LCD_putchar('*');
	LCD_putchar('*');
	LCD_putchar('*');
	LCD_putchar('\n');
	LCD_putchar(' ');
	LCD_putchar(' ');
	LCD_putchar('A');
	LCD_putchar('l');
	LCD_putchar('a');
	LCD_putchar('r');
	LCD_putchar('m');
	LCD_putchar(' ');
	LCD_putchar('a');
	LCD_putchar('r');
	LCD_putchar('m');
	LCD_putchar('e');
	LCD_putchar('d');

}

void disarm()
{
	armed=0;
	LCD_colour(Blue);
	LCD_clear();
	LCD_home();
	LCD_putchar('*');
	LCD_putchar('*');
	LCD_putchar('*');
	LCD_putchar(' ');
	LCD_putchar('A');
	LCD_putchar('l');
	LCD_putchar(' ');
	LCD_putchar('B');
	LCD_putchar('e');
	LCD_putchar('r');
	LCD_putchar('r');
	LCD_putchar('y');
	LCD_putchar(' ');
	LCD_putchar('*');
	LCD_putchar('*');
	LCD_putchar('*');
	LCD_putchar('\n');
	LCD_putchar(' ');
	LCD_putchar('A');
	LCD_putchar('l');
	LCD_putchar('a');
	LCD_putchar('r');
	LCD_putchar('m');
	LCD_putchar(' ');
	LCD_putchar('d');
	LCD_putchar('i');
	LCD_putchar('s');
	LCD_putchar('a');
	LCD_putchar('r');
	LCD_putchar('m');
	LCD_putchar('e');
	LCD_putchar('d');
}

void protectMemory()
{
	uintptr_t page_base;
	page_base = ((uintptr_t)rawBuffer / PAGE_SIZE ) * PAGE_SIZE;
	mprotect((void*)page_base, 1, PROT_READ | PROT_WRITE);
	//mprotect((void*)page_base, 1, PROT_READ | PROT_WRITE | PROT_EXEC);
}

void init()
{
	static struct termios newt;
	tcgetattr( STDIN_FILENO, &newt);
	newt.c_lflag &= ~(ICANON);
	tcsetattr( STDIN_FILENO, TCSANOW, &newt);

	protectMemory();

	arm();

	readPasswd();
	readFlag();
	logged=0;
}

void checkPassword()
{
	char buffer[20];

	gets(buffer);
	hash2(buffer,strlen(buffer),hashBuffer);
	if (!memcmp(currentPasswd,hashBuffer,32))
	{
		logged=1;
		return;
	}
}

changePassword()
{
	char msg1[]="\nEnter the new password: ";
	char msg2[]="\nPassword accepted but not changed, because it'll kill the challenge :)\n";

	puts(msg1);
	fgets(rawBuffer,666,stdin);
	puts(msg2);
}

void clearScreen()
{
	printf("\x0C");
}

void runMain()
{
	char c[300];

	if (!logged)
	{
		clearScreen();
		printf("############################################\n# Welcome to Al Berry interface management #\n############################################\n");
		if (armed)
		{
			printf("#          Your alarm is *ARMED*           #");
		}
		else
		{
			printf("#        Your alarm is *DISARMED*          #");
		}
		printf("\n############################################\n\nPassword: ");
		checkPassword();
	}
	else
	{
		while(1)
		{
			clearScreen();
			printf("######################\n#      Main menu     #\n######################\n# 1) Arm alarm       #\n# 2) Disarm alarm    #\n# 3) Change password #\n# 4) Log out         #\n######################\n\nSelect: ");
			c[0]=getchar();
			switch(c[0])
			{
				case '1':
					printf("\nAlarm armed\n");
					arm();
					sleep(3);
					return;
				case '2':
					printf("\nAlarm disarmed\n");
					printf("Your flag is: %s\n",flagDisarmed);
					disarm();
					sleep(9);
					return;	
				case '3':
					changePassword();
					sleep(4);
					return;
				case '4':
					printf("\nDisconnecting from alarme\n");
					logged=0;
					sleep(3);
					return;
			}
		}
	}
}

int main()
{
	GPIO_open();
	LCD_init(0);
	LCD_clear();

	init();

	while(1)
	{
		runMain();
	}

		//usleep(900*1000);
}

