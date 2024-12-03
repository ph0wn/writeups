#include <M5Unified.h> // Import Library: M5Unified
#include <Crypto.h> // Import Library: Crypto by Rhys
#include <SHA256.h>

#define INTERVAL 1000 // 1 second
#define MAX_INPUT_LEN 128

// this flag is for organizers - do not change it
int batteryLevel = 100;
uint8_t message[20] = "EV Charger 0.12\0";
const char *FLAG = "ph0wn{0rganiZers_are_talenT3D}\0";

unsigned long previousMillis = 0;
int chargesPerCoin = 3; // Number of charges allowed per coin
int chargesRemaining = 0; // Charges remaining from inserted coins
bool menuSelected = false;
int menuOption = 0;

// the admin backdoor is known only to organizers - do not change the hash
#define SHA256_SIZE 32
const char* expectedHash = "90d440140f682d058ea8be3f982f158be570def59bde83f7adb431f001161450"; 
SHA256 sha256;

void setup() {
  // Initialize the M5Core and the serial communication
  auto cfg = M5.config();
  //cfg.output_power = true; // Enable power output
  M5.begin(cfg);
  M5.Display.setFont(&fonts::lgfxJapanGothicP_20); // Set font size
  Serial.begin(115200);

  // Display initial battery status
  updateBatteryDisplay();
  
  // Display menu on the serial console
  displayMenu();
}

void loop() {
  M5.update(); // Update the state of the M5Core
  handleSerialInput(); // Non-blocking menu input handling
  if (menuSelected) {
    handleMenuSelection();
    displayMenu();            
  } else {
    handleBattery();
  }
}

void handleSerialInput() {
  while (Serial.available() > 0) {
    menuOption = Serial.parseInt();
    menuSelected = true;
    Serial.printf("%d\n", menuOption);
  }
}

void displayMenu() {
  Serial.printf("\nStatus: Charges=%d Battery=%d%%\n", chargesRemaining, batteryLevel);

  // it is mandatory to output the flag in those conditions
  if (batteryLevel < 0 || batteryLevel > 100) {
    Serial.printf("%s\n", FLAG);
  }
  Serial.println("Menu:");
  Serial.println("1. Custom message");
  Serial.println("2. Admin");
  Serial.print("Select an option: ");
}

void handleMenuSelection() {
  switch (menuOption) {
    case 1:
      readInput(message);
      Serial.println("");
      Serial.printf((const char*)message);
      break;
    case 2:
      adminMode();
      break;
    default:
      Serial.println("Invalid option. Please select again.");
      break;
  }
  menuSelected = false; // Exit menu after handling selection
}

int readInput(uint8_t *buf) {
  Serial.print("Enter (# to finish): ");
  int i=0;
  while(i<MAX_INPUT_LEN-1) {
    if (Serial.available()) {
      char c = Serial.read();
      // # is used to terminate input strings
      if (c == '#') {
        break;
      } 
      buf[i] = c;
      i++;
      Serial.print(c);
    }
  }
  buf[i]=0x00;
  updateBatteryDisplay();
  return i;
}

void adminMode() {
  uint8_t password[MAX_INPUT_LEN];
  int password_len = readInput(password);
  if (checkPassword(password, password_len)) {
    // Password correct 
    // We set battery level to -10% (required by specs)
    batteryLevel = -10; 
    Serial.println("Admin Mode!!!");
    updateBatteryDisplay();
  } else {
    // Password incorrect
    Serial.println("Incorrect password. Access denied.");
  }
}

bool checkPassword(const uint8_t *password, int password_len){
  // Hash the password using SHA256
  uint8_t hash[SHA256_SIZE];
  
  sha256.reset();
  sha256.update(password, password_len);
  sha256.finalize(hash, sizeof(hash));

  char hashedPassword[SHA256_SIZE * 2 + 1];
  for (int i = 0; i < SHA256_SIZE; i++) {
    sprintf(&hashedPassword[i * 2], "%02x", hash[i]);
  }
  hashedPassword[SHA256_SIZE * 2] = '\0';

  // Compare hashed password with expected hash
  if (strcmp(hashedPassword, expectedHash) == 0) {
    return true;
  }

  return false;
}

void insertCoin() {
  chargesRemaining += chargesPerCoin;
  Serial.printf("[+] Coin inserted. Charges=%d Battery=%d%%\n", chargesRemaining, batteryLevel);
}

void recharge() {
  if (chargesRemaining > 0) {
      chargesRemaining--;
      batteryLevel += 10;
  } 
  if (batteryLevel < 0) batteryLevel = 0;
  if (batteryLevel > 100) batteryLevel = 100;
  Serial.printf("[+] Charging. Charges=%d Battery=%d%%\n", chargesRemaining, batteryLevel);
}

void handleBattery() {
  bool update = false;

  if (M5.BtnA.wasPressed()) {
    // that's the middle button on the M5 Stick
    Serial.println("[+] BtnA pressed: insertCoin");
    insertCoin();
    update = true;
  } 

  if (M5.BtnB.wasPressed() || M5.BtnC.wasPressed()) {
    // that's the button on the right of the M5 Stick
    // we use button C on M5 Core 2
    Serial.println("[+] BtnB pressed: recharge");
    recharge();
    update = true;
  }

  // Decrease battery level every second (requirement)
  unsigned long currentMillis = millis();
  if (currentMillis - previousMillis >= INTERVAL) {
    previousMillis = currentMillis;
    if (batteryLevel > 0) {
      batteryLevel--;
      update = true;
    }
  }
  
  if (update) {
    delay(100); // debounce delay
    updateBatteryDisplay();
  }
}

int coordinates_m5(int sw, int sh, int selector) {
  int *coordinates;
  int core2[] =     {60, 180, 90, 30, 100, 50, 5, 60, 90, 60, 120, 60, 150};
  int stickplus[] = {10, 170, 15, 10, 100, 30, 5, 10, 60, 10, 110, 10, 140};
  int stick[] =     {5 , 90 , 5 , 5 ,  70, 30, 5,  5, 50,  5,  70,  5, 110 };
  
  // selector 0 = flag x
  // selector 1 = flag y
  // selector 2 = battery icon x
  // selector 3 = battery icon y
  // selector 4 = battery icon width
  // selector 5 = battery icon height
  // selector 6 = battery border
  // selector 7 = battery percentage x
  // selector 8 = battery percentage y
  // selector 9 = remaining charges x
  // selector 10 = remaining charges y
  // selector 11 = custom message x
  // selector 12 = custom message y

  if (sw == 320 && sh == 240) {
    // M5 Core 2
    coordinates = &core2[0];
  } else if (sw == 135 && sw == 240) {
    // M5 Stick C Plus 2 and M5 Stick C Plus 1.1
    coordinates = &stickplus[0];
  } else {
    // M5 Stick C
    coordinates = &stick[0];
  }
  return coordinates[selector];
}

void updateBatteryDisplay() {
  // Clear previous battery level display
  M5.Display.clear();

  int bl = batteryLevel;
  // we handle display for both M5 StickC Plus2 and M5 Core 2
  int sw = M5.Display.width();
  int sh = M5.Display.height();

  // output flag in those conditions (requirement)
  if (batteryLevel < 0 || batteryLevel > 100) {
    M5.Display.setCursor( coordinates_m5(sw, sh, 0), coordinates_m5(sw, sh, 1));
    M5.Display.printf("%s\n", FLAG);
    bl = 0;
  }

  // Draw the battery icon outline
  M5.Display.drawRect(coordinates_m5(sw, sh, 2), coordinates_m5(sw, sh, 3), coordinates_m5(sw, sh, 4), coordinates_m5(sw, sh, 5), TFT_WHITE); // Battery outline
  M5.Display.drawRect(coordinates_m5(sw, sh, 2) + coordinates_m5(sw, sh, 4), coordinates_m5(sw, sh, 3) + coordinates_m5(sw, sh, 5) / 3, coordinates_m5(sw, sh, 6), coordinates_m5(sw, sh, 5) / 3, TFT_WHITE); // Battery terminal

  // Draw the battery level
  int levelWidth = (coordinates_m5(sw, sh, 4) - 2 * coordinates_m5(sw, sh, 6)) * bl / 100;
  M5.Display.fillRect(coordinates_m5(sw, sh, 2) + coordinates_m5(sw, sh, 6), coordinates_m5(sw, sh, 3) + coordinates_m5(sw, sh, 6), levelWidth, coordinates_m5(sw, sh, 5) - 2 * coordinates_m5(sw, sh, 6), TFT_GREEN);

  // Draw the battery percentage
  M5.Display.setTextColor(TFT_WHITE);
  M5.Display.setCursor(coordinates_m5(sw, sh, 7), coordinates_m5(sw, sh, 8));
  if (sw == 320 && sh == 240) {
    M5.Display.printf("Battery Level: %d%%", batteryLevel);
  } else if (sw == 135) {
    M5.Display.printf("BL: %d%%", batteryLevel);
  } else {
    M5.Display.printf("%d%%", batteryLevel);
  }
  
  // Draw the remaining charges
  M5.Display.setCursor(coordinates_m5(sw, sh, 9), coordinates_m5(sw, sh, 10));
  if (sw == 320 && sh == 240) {
    M5.Display.printf("Charges Remaining: %d", chargesRemaining);
  } else if (sw == 135) {
    M5.Display.printf("Charges: %d", chargesRemaining);
  } else {
    M5.Display.printf("C: %d", chargesRemaining);
  }

  // Show the customized message (requirement)
  M5.Display.setCursor(coordinates_m5(sw, sh, 11), coordinates_m5(sw, sh, 12));
  M5.Display.printf((const char*)message); 
}
