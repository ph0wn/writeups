#include <M5CoreInk.h>
#include <Preferences.h>

Preferences preferences;
Ink_Sprite InkPageSprite(&M5.M5Ink);

void setup() {
  M5.begin();
  digitalWrite(LED_EXT_PIN, LOW); // turn LED on
  M5.M5Ink.isInit();
  M5.M5Ink.clear(); 
  delay(100);
  if( InkPageSprite.creatSprite(0,0,200,200,true) != 0 ){
    Serial.println("[-] ERROR: Ink Sprite create failed");
  }
  
  // put your setup code here, to run once:
  Serial.println("--------- ph0wn: FOR ORGANIZERS ONLY - THIS IS SECRET! --------");
  preferences.begin("ph0wn-challenge", false); // RW = false
  preferences.clear(); // remove all previous data
  preferences.putInt("key", 0x44); 
  preferences.putString("stage1", "ph0wn{stage1_read_the_nvs}");
  preferences.putInt("queen", 0x1926);
  preferences.putInt("james-bond", 0x0007);
  preferences.putInt("felix-leiter", 0x1337);
  preferences.end();
  Serial.println("[+] setup() done");
  
}

void loop() {
  Serial.println("Bye!");
  Serial.println("-------------------");
  InkPageSprite.clear();
  delay(50);
  InkPageSprite.drawString(10, 50, "Flashed", &AsciiFont24x48);
  InkPageSprite.pushSprite();
  delay(50);
  
  digitalWrite(LED_EXT_PIN, HIGH); // turn LED off
  M5.shutdown();
}
