// ---------------------------------------  \\
//                                          \\
//     SLOT MACHINE for BADGE CONTEST       \\
//             PHOWN CTF                    \\
//       (c) 2021/22  6502man               \\
//                                          \\
// ---------------------------------------  \\



//  sd 6502man
#include <SPI.h>          // f.k. pour Arduino-1.5.2
#include <SD.h>


// touch screen 6502man
#include <stdint.h>
#include "TouchScreen.h"
// These are the pins for the shield!
#define YP A1  // must be an analog pin, use "An" notation!
#define XM A2  // must be an analog pin, use "An" notation!
#define YM 7   // can be a digital pin
#define XP 6   // can be a digital pin
TouchScreen ts = TouchScreen(XP, YP, XM, YM, 300);


// grafik screen 6502man
#include <Adafruit_GFX.h> // Hardware-specific library
#include <MCUFRIEND_kbv.h>

//----------------------------------------|
// TFT Breakout       -- Arduino UNO / Mega2560 / OPEN-SMART UNO Black
// GND                -- GND
// 3V3                -- 3.3V
// CS                 -- A3
// RS                 -- A2
// WR                 -- A1
// RD                 -- A0
// RST                -- RESET
// LED                -- GND
// DB0                -- 8
// DB1                -- 9
// DB2                -- 10
// DB3                -- 11
// DB4                -- 4
// DB5                -- 13
// DB6                -- 6
// DB7                -- 7
MCUFRIEND_kbv tft;
#define SD_CS 5

File root;
char namebuf[32] = "slot.bmp";
int pathlen;
uint8_t         spi_save;
File SaveStats;

// var for slotmachine  6502man
int barel1,barel2,barel3;
bool JackpotFire;   // flag en cas de jackpot (animation lumières)
int Playing=0;        // nombre de parties
int wining=0;         // nombre de parties gagnees
int losing=0;         // nombre de parties perdues
int jackpot=0;        // nombre de jackpot remporté
#define LED 3       //  digital pin (jackpot leds)
#include <EEPROM.h>


#define BLACK   0x0000
#define BLUE    0x001F
#define RED     0xF800
#define GREEN   0x07E0
#define CYAN    0x07FF
#define MAGENTA 0xF81F
#define YELLOW  0xFFE0
#define WHITE   0xFFFF

void setup()
{
// random  6502man
  randomSeed(analogRead(0));
  barel1=1;   // initialise barels
  barel2=1;   //  "
  barel3=1;   //  "
  
/*  
  Playing=EEPROM.read(0);
  wining=EEPROM.read(1);
  losing=EEPROM.read(2);
  jackpot=EEPROM.read(3);
*/

//  Jackpot  LED
  pinMode(LED, OUTPUT);  
  digitalWrite(LED, LOW);
  JackpotFire=false;


  uint16_t ID;
  ID = tft.readID();
    
 
  tft.begin(0x7793);//to enable ST7793 driver code
  tft.fillScreen(0x0000);
 
  tft.setRotation(3);
  bool good = SD.begin(SD_CS);
  if (!good) {
        Serial.print(F("cannot start SD"));
        while (1);
    }
   bmpDraw("slotd.bmp", 0, 0);
   bmpDraw("Fruits1.bmp", 45, 145);
   bmpDraw("Fruits1.bmp", 110, 145);
   bmpDraw("Fruits1.bmp", 175, 145);
  
   /*
    tft.setCursor(30, 20);
   tft.setTextColor(CYAN);
   tft.setTextSize(1);
   tft.println("Touch Screen for start machine");
   */
 }
 
 void loop()
 {
  TSPoint p = ts.getPoint();
    pinMode(XM, OUTPUT);
    pinMode(YP, OUTPUT);
    pinMode(XP, OUTPUT);
    pinMode(YM, OUTPUT);

  if ( JackpotFire==true)
  {
      int JKP=10;
      while (JKP>0)
        {
          Jackpot();
          JKP--;
        }
        JackpotFire=false;
  }
 
  if (p.z > 100 && p.x >700 && p.y>730) 
    {  
      digitalWrite(LED, LOW);
      JackpotFire=false;
      tft.fillRect(30, 60, 280,25, BLACK);
      RollBarells();
      Playing++;
     

//stats on EEPROM
//    EEPROM.update(0, Playing);
//    EEPROM.update(1, wining);
//    EEPROM.update(2, losing);
//    EEPROM.update(3, jackpot);
     
//stats on file
    SPCR = spi_save;
    SD.remove("Stats.txt");
    SaveStats = SD.open("Stats.txt", FILE_WRITE);
		
    SaveStats.println(Playing); 
    SaveStats.println(wining); 
    SaveStats.println(losing); 
    SaveStats.println(jackpot); 
    SaveStats.println();   
    SaveStats.close();
    SPCR = 0;  
//--------
 
  
    }

 }



void RollBarells(void)
{
  int k1=random(30, 36);  // arret aleatoire 
  int k2=random(30, 36)+8;  //
  int k3=random(30, 36)+24;
       
  char FNM[12] = "Fruits1.bmp\0";

  while (k1+k2+k3>0)
  {
    if (barel1>7) {barel1=1;}
    if (barel2>7) {barel2=1;}
    if (barel3>7) {barel3=1;}
   if (k1-->0)
    {    FNM[6]=(barel1++)+48; bmpDraw(FNM, 45, 145); }
    if (k2-->0)
    {    FNM[6]=(barel2++)+48; bmpDraw(FNM, 110, 145);}
   if (k3-->0)
    {    FNM[6]=(barel3++)+48; bmpDraw(FNM, 175, 145); }
    delay(10);
  }
    if (barel1>7) {barel1=1;} // correctif evite différence en cas
    if (barel2>7) {barel2=1;} // d'arret du dernier rouleau sur
    if (barel3>7) {barel3=1;} // 7++ (8)

  if (barel1==1 && barel1==barel2 && barel2==barel3)
    {
      tft.setCursor(30, 60);
      tft.setTextColor(YELLOW);
      tft.setTextSize(3);
      tft.println("YOU WIN JACKPOT");
      wining++; 
      JackpotFire=true; 
      Jackpot();
    }
   else if (barel1==barel2 && barel2==barel3)
   {
      tft.setCursor(30, 60);
      tft.setTextColor(YELLOW);
      tft.setTextSize(3);
      tft.println("YOU WIN 3 CANDY");
      wining++;
    }
  else if (barel1==barel2 || barel1==barel3 || barel2==barel3)
  {
    tft.setCursor(30, 60);
    tft.setTextColor(YELLOW);
    tft.setTextSize(3);
    tft.println("YOU WIN 1 CANDY");
    wining++;
  }
  else 
  {
     tft.setCursor(30, 60);
    tft.setTextColor(RED);
    tft.setTextSize(3);
    tft.println("YOU LOSE");
    losing++;  
  }
   
}


void Jackpot(void)
{
      digitalWrite(LED, HIGH);
      delay (100);
      digitalWrite(LED, LOW);
      delay (200);
}






// --------------------------------
//  partie de la librairie
//  d'affichage d'un BMP (dépendant 
//  du driver LCD).
// --------------------------------
 
 // This function opens a Windows Bitmap (BMP) file and
 // displays it at the given coordinates.  It's sped up
 // by reading many pixels worth of data at a time
 // (rather than pixel by pixel).  Increasing the buffer
 // size takes more of the Arduino's precious RAM but
 // makes loading a little faster.	20 pixels seems a
 // good balance.
 
#define BUFFPIXEL 20
 
 void bmpDraw(char *filename, int x, int y) {
   File 	bmpFile;
   int		bmpWidth, bmpHeight;   // W+H in pixels
   uint8_t	bmpDepth;			   // Bit depth (currently must be 24)
   uint32_t bmpImageoffset; 	   // Start of image data in file
   uint32_t rowSize;			   // Not always = bmpWidth; may have padding
   uint8_t	sdbuffer[3*BUFFPIXEL]; // pixel in buffer (R+G+B per pixel)
   uint16_t lcdbuffer[BUFFPIXEL];  // pixel out buffer (16-bit per pixel)
   uint8_t	buffidx = sizeof(sdbuffer); // Current position in sdbuffer
   boolean	goodBmp = false;	   // Set to true on valid header parse
   boolean	flip	= true; 	   // BMP is stored bottom-to-top
   int		w, h, row, col;
   uint8_t	r, g, b;
   uint32_t pos = 0, startTime = millis();
   uint8_t	lcdidx = 0;
   boolean	first = true;
 
   if((x >= tft.width()) || (y >= tft.height())) return;
 
//   Serial.println();
//   Serial.print("Loading image '");
//   Serial.print(filename);
//   Serial.println('\'');
   // Open requested file on SD card
   SPCR = spi_save;
   if ((bmpFile = SD.open(filename)) == NULL) {
//	 Serial.print("File not found");
	 return;
   }
 
   // Parse BMP header
   if(read16(bmpFile) == 0x4D42) { // BMP signature
//	 Serial.print(F("File size: ")); Serial.println(read32(bmpFile));
read32(bmpFile);
	 (void)read32(bmpFile); // Read & ignore creator bytes
	 bmpImageoffset = read32(bmpFile); // Start of image data
//	 Serial.print(F("Image Offset: ")); Serial.println(bmpImageoffset, DEC);
	 // Read DIB header
//	 Serial.print(F("Header size: ")); Serial.println(read32(bmpFile));
read32(bmpFile);
	 bmpWidth  = read32(bmpFile);
	 bmpHeight = read32(bmpFile);
	 if(read16(bmpFile) == 1) { // # planes -- must be '1'
	   bmpDepth = read16(bmpFile); // bits per pixel
//	   Serial.print(F("Bit Depth: ")); Serial.println(bmpDepth);
	   if((bmpDepth == 24) && (read32(bmpFile) == 0)) { // 0 = uncompressed
 
		 goodBmp = true; // Supported BMP format -- proceed!
//		 Serial.print(F("Image size: "));
//		 Serial.print(bmpWidth);
//		 Serial.print('x');
//		 Serial.println(bmpHeight);
 
		 // BMP rows are padded (if needed) to 4-byte boundary
		 rowSize = (bmpWidth * 3 + 3) & ~3;
 
		 // If bmpHeight is negative, image is in top-down order.
		 // This is not canon but has been observed in the wild.
		 if(bmpHeight < 0) {
		   bmpHeight = -bmpHeight;
		   flip 	 = false;
		 }
 
		 // Crop area to be loaded
		 w = bmpWidth;
		 h = bmpHeight;
		 if((x+w-1) >= tft.width())  w = tft.width()  - x;
		 if((y+h-1) >= tft.height()) h = tft.height() - y;
 
		 // Set TFT address window to clipped image bounds
		 SPCR = 0;
		 tft.setAddrWindow(x, y, x+w-1, y+h-1);
 
		 for (row=0; row<h; row++) { // For each scanline...
		   // Seek to start of scan line.  It might seem labor-
		   // intensive to be doing this on every line, but this
		   // method covers a lot of gritty details like cropping
		   // and scanline padding.  Also, the seek only takes
		   // place if the file position actually needs to change
		   // (avoids a lot of cluster math in SD library).
		   if(flip) // Bitmap is stored bottom-to-top order (normal BMP)
			 pos = bmpImageoffset + (bmpHeight - 1 - row) * rowSize;
		   else 	// Bitmap is stored top-to-bottom
			 pos = bmpImageoffset + row * rowSize;
		   SPCR = spi_save;
		   if(bmpFile.position() != pos) { // Need seek?
			 bmpFile.seek(pos);
			 buffidx = sizeof(sdbuffer); // Force buffer reload
		   }
 
		   for (col=0; col<w; col++) { // For each column...
			 // Time to read more pixel data?
			 if (buffidx >= sizeof(sdbuffer)) { // Indeed
			   // Push LCD buffer to the display first
			   if(lcdidx > 0) {
				 SPCR	= 0;
				 tft.pushColors(lcdbuffer, lcdidx, first);
				 lcdidx = 0;
				 first	= false;
			   }
			   SPCR = spi_save;
			   bmpFile.read(sdbuffer, sizeof(sdbuffer));
			   buffidx = 0; // Set index to beginning
			 }
 
			 // Convert pixel from BMP to TFT format
			 b = sdbuffer[buffidx++];
			 g = sdbuffer[buffidx++];
			 r = sdbuffer[buffidx++];
			 lcdbuffer[lcdidx++] = tft.color565(r,g,b);
		   } // end pixel
		 } // end scanline
		 // Write any remaining data to LCD
		 if(lcdidx > 0) {
		   SPCR = 0;
		   tft.pushColors(lcdbuffer, lcdidx, first);
		 } 
//		 Serial.print(F("Loaded in "));
//		 Serial.print(millis() - startTime);
//		 Serial.println(" ms");
	   } // end goodBmp
	 }
   }
 
   bmpFile.close();
   if(!goodBmp) Serial.println("BMP format non reconnu.");
 }
 
 // These read 16- and 32-bit types from the SD card file.
 // BMP data is stored little-endian, Arduino is little-endian too.
 // May need to reverse subscript order if porting elsewhere.
 
 uint16_t read16(File f) {
   uint16_t result;
   ((uint8_t *)&result)[0] = f.read(); // LSB
   ((uint8_t *)&result)[1] = f.read(); // MSB
   return result;
 }
 
 uint32_t read32(File f) {
   uint32_t result;
   ((uint8_t *)&result)[0] = f.read(); // LSB
   ((uint8_t *)&result)[1] = f.read();
   ((uint8_t *)&result)[2] = f.read();
   ((uint8_t *)&result)[3] = f.read(); // MSB
   return result;
 }
 
