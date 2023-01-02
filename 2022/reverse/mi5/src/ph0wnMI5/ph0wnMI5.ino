#include <M5CoreInk.h>
#include <EEPROM.h>
#include <Preferences.h>
#include "myimages.h"

typedef struct {
    int width;
    int height;
    int bitCount;
    unsigned char *ptr;
}image_t;

image_t picoImage = { 200, 153, 1, pico };
image_t ph0wnImage = { 200, 84, 1, ph0wn };


Preferences preferences;
Ink_Sprite InkPageSprite(&M5.M5Ink);

// current address to decrypt memory
static int readAddr = 0;

// decrypted flag is displayed on 2 lines
static char flagLine1[20+1];
static char flagLine2[20+1];

void readFlag(int base_addr, int key, size_t len) {
 memset(flagLine1,0x00,21);
 memset(flagLine2,0x00,21);

 // check we won't overflow
 if (len>40) {
  Serial.println("[-] flag len is too big");
  abort();
 }
 
 Serial.printf("Reading at addr=%d (0x%08x) key=0x%02x len=%d...\n",base_addr, base_addr, key, len);
 for (int i = 0; i < len; i++){
    uint8_t c = EEPROM.read(base_addr+i) ^ key;
    if ((c < 32) || (c> 125)) {
      c = '*';
    } 
    Serial.printf("%c",c); 
    if (i<20) {
      flagLine1[i]=c;
    } else {
      flagLine2[i-20]=c;
    }
 }
 Serial.print("\n---------------------------------\n");
 Serial.printf("line1=%s\n",flagLine1);
 Serial.printf("line2=%s\n",flagLine2);
}

void writeSecret(int addr, const char *buf, size_t len) {
  for (int i=0; i<len; i++) {
    EEPROM.write(addr+i, buf[i]);
  }
  Serial.printf("[+] writeSecret(): wrote %d bytes to EEPROM\n", len);
 
}

int readKey() {
  preferences.begin("ph0wn-challenge", true);
  int key = preferences.getInt("key", 0);
  preferences.end();
  Serial.printf("[+] Reading NVS preferences: key=0x%02x\n",key);
  return key;
}

void splashScreen() {
  InkPageSprite.drawBuff( 0, 0, picoImage.width, picoImage.height, picoImage.ptr );
  InkPageSprite.drawString(50, 170, "Btn MID: help");

  InkPageSprite.pushSprite();
  delay(1000);
  Serial.println("[+] splashScreen() displayed. Pico is so charming ;)");
}

void instructionsScreen() {
  InkPageSprite.drawBuff( 0, 0, picoImage.width, picoImage.height, picoImage.ptr );
  InkPageSprite.drawString(10, 154, "Btn UP: select account");
  InkPageSprite.drawString(10, 186, "Btn DOWN: list account");
  InkPageSprite.drawString(10, 170, "Btn A (top): read accnt");
  
  InkPageSprite.pushSprite();
  Serial.println("[+] instructionScreen() displayed - RTFM");
}

void readScreen() {
  char buf[10];
  snprintf(buf, 5, "%04x", readAddr);
  Serial.printf("readScreen(): addr=%d (0x%08x)\n", readAddr, readAddr);
    
  InkPageSprite.drawBuff( 0, 0, ph0wnImage.width, ph0wnImage.height, ph0wnImage.ptr );
  InkPageSprite.drawString(50, 80, buf, &AsciiFont24x48);
  
  if (flagLine1[0] != '\0') {
    InkPageSprite.drawString(10, 130, flagLine1, &AsciiFont8x16);
  }
  if (flagLine2[0] != '\0') {
    InkPageSprite.drawString(10, 150, flagLine2, &AsciiFont8x16);
  }
  InkPageSprite.drawString(50, 170, "Btn MID: Help", &AsciiFont8x16);
  InkPageSprite.pushSprite();
}

void listScreen() {
  InkPageSprite.drawString( 60, 10, "MI5", &AsciiFont24x48);
  InkPageSprite.drawString( 40, 50, "secret vault");
  InkPageSprite.drawString( 30, 110, "1926 - HRH Queen");
  InkPageSprite.drawString( 30, 130, "Elizabeth II");
  InkPageSprite.drawString(50, 170, "Btn MID: Help", &AsciiFont8x16);
  InkPageSprite.pushSprite();
  Serial.println("[+] listScreen()");
}

void accountMenu() {
  
  int i = 0;
  uint8_t account[4] = { 0x0f, 0x0f, 0x0f, 0x0f};

  InkPageSprite.drawString(50, 10, "MI5 Vault");
  InkPageSprite.drawString(10, 30, "Btn UP: inc digit");
  InkPageSprite.drawString(10, 50, "Btn DOWN: inc digit");
  InkPageSprite.drawString(10, 70, "Btn A or MID: ENTER/OK");
  //InkPageSprite.FillRect(20,105,120,58,0);
  InkPageSprite.pushSprite();

  while (i<4) {
    M5.update();
    
    if(M5.BtnUP.wasPressed()){ 
      //M5.Speaker.beep();
      if (account[i] < 0x0f) {
        account[i] = account[i] + 1;
      } else {
        account[i] = 0x00;
      }
    }
    
    if(M5.BtnDOWN.wasPressed()){ 
      //M5.Speaker.beep();
      if (account[i] > 0x00 ) {
        account[i] = account[i] -1;
      } else {
        account[i] = 0x0f;
      }
    }
    
    if(M5.BtnMID.wasPressed() || M5.BtnEXT.wasPressed()){ 
      M5.Speaker.beep();
      i = i + 1;
      Serial.printf("MID/EXT -> ENTER: account[%d]=%1x\n", i-1, account[i-1]);
    }

    // display address
    char buf[5];
    readAddr = account[0] << 12 | account[1] << 8 | account[2] << 4 | account[3];
    snprintf(buf, 5, "%04x", readAddr);
    InkPageSprite.drawString(30, 110, buf, &AsciiFont24x48);

    // display position marker
    if (i == 0) InkPageSprite.drawString(30, 150, "^", &AsciiFont24x48);
    if (i == 1) InkPageSprite.drawString(30, 150, " ^", &AsciiFont24x48);
    if (i == 2) InkPageSprite.drawString(30, 150, "  ^", &AsciiFont24x48);
    if (i == 3) InkPageSprite.drawString(30, 150, "   ^", &AsciiFont24x48);
    InkPageSprite.pushSprite();
    
    delay(10);
  }

  // check value
  if (readAddr > 10000) {
      Serial.println("[-] too big bank account nb");
      readAddr = 9999;
    } 
  
  Serial.printf("[+] accountMenu(): readAddr=%d (%04x)\n", readAddr, readAddr);
}


void setup() {
  M5.begin();
  digitalWrite(LED_EXT_PIN, LOW); // turn LED on
  
  if( !M5.M5Ink.isInit()){ 
    Serial.printf("Ink Init failed");
    while (1) delay(100);
  }
  
  M5.M5Ink.clear(); 
  delay(1000);

  Serial.println("======= ph0wn MI5 challenge setup ========\n");

  // writing the flag
  int addr=0x26ac; // secret address for the flag - can't put it after 10000 it seems?
  const char encoded_flag[] = { 0x34,0x2c,0x74,0x33,0x2a,0x3f,0x27,0x2b,0x2a,0x23,0x36,0x25,0x30,0x37,0x1b,0x34,0x2d,0x27,0x2b,0x1b,0x3d,0x2b,0x31,0x1b,0x22,0x2b,0x31,0x2a,0x20,0x1b,0x30,0x2c,0x21,0x1b,0x23,0x25,0x2a,0x23,0x65,0x39 };
  size_t len = 40;
  if (!EEPROM.begin(addr+len)){  // maximum address
    Serial.println("\nFailed to initialise EEPROM!"); 
    delay(1000000);
  }
  writeSecret(addr, encoded_flag, len);

  // writing the accounts
  addr=0x1926;
  const char queen[] = { 0x5,0x36,0x21,0x64,0x3d,0x2b,0x31,0x64,0x37,0x34,0x3d,0x2d,0x2a,0x23,0x64,0x2c,0x21,0x36,0x64,0x9,0x25,0x2e,0x21,0x37,0x30,0x3d,0x7b,0x64,0x17,0x2c,0x2b,0x27,0x2f,0x2d,0x2a,0x23,0x65 };
  len = 37;
  writeSecret(addr, queen, len);

  const char james[] = { 0xd,0x64,0x28,0x2d,0x2f,0x21,0x64,0x32,0x2b,0x20,0x2f,0x25,0x69,0x29,0x25,0x36,0x30,0x2d,0x2a,0x2d,0x68,0x64,0x37,0x2c,0x25,0x2f,0x21,0x2a,0x64,0x2a,0x2b,0x30,0x64,0x37,0x30,0x2d,0x36,0x36,0x21,0x20 };
  len = 40;
  addr = 0x0007;
  writeSecret(addr, james, len);

  const char leiter[] = { 0x2b,0x2c,0x64,0x20,0x21,0x25,0x36,0x68,0x64,0x7,0xd,0x5,0x64,0x2c,0x25,0x37,0x64,0x2d,0x2a,0x22,0x2d,0x28,0x30,0x36,0x25,0x30,0x21,0x20,0x64,0x30,0x2b,0x2b,0x7b };
  len = 33;
  addr = 0x1337;
  writeSecret(addr, leiter, len);

  // clear the display flag lines
  memset(flagLine1,0x00,21);
  memset(flagLine2,0x00,21);

  // create display area on the full screen
  if( InkPageSprite.creatSprite(0,0,200,200,true) != 0 ){
    Serial.printf("Ink Sprite create failed");
  }
  
  // splash screen
  splashScreen();

  // set beep sound
  M5.Speaker.setBeep(443,10);
  
  Serial.print("[+] Setup finished\n");
  Serial.print("---------------------------------\n");
}



void loop() {
  boolean refresh = false;
  M5.update();  // Check button down state. 

  if (M5.BtnEXT.isPressed() || M5.BtnUP.wasPressed() || M5.BtnMID.wasPressed() || M5.BtnDOWN.wasPressed()) {
    // initial actions for supported buttons
    // M5.Speaker.beep();
    InkPageSprite.clear();
    delay(100);
    // specific actions are below
  }

  if( M5.BtnMID.wasPressed()) {
    Serial.printf("Button MID Pressed\n");
    instructionsScreen();
  } 

  if ( M5.BtnDOWN.wasPressed()) {
    Serial.printf("Button DOWN pressed\n");
    listScreen();
  }
  
  if(M5.BtnEXT.wasPressed()){ 
    Serial.printf("Button A pressed\n");
    int key = readKey();
    readFlag(readAddr, key, 40);
    readScreen();
  }

  if( M5.BtnUP.wasPressed()) {
    Serial.printf("Button UP pressed\n");
    accountMenu();
    InkPageSprite.clear();
    delay(50);
    int key = readKey();
    readFlag(readAddr, key, 40);
    readScreen();
  }
  
  delay(10);
}
