#include <Arduino.h>
#include <Keypad.h>
#include <stdint.h>
#include <string.h>
#include <Wire.h>
#include <U8g2lib.h>

// OLED (I2C, LOW RAM page buffer mode)
// If your module is 0x3C (most are), this works on Uno.
U8G2_SSD1306_128X64_NONAME_1_HW_I2C u8g2(U8G2_R0, /* reset=*/ U8X8_PIN_NONE);

// LOCK
#define LOCK_PIN 10

void open_lock() {
  digitalWrite(LOCK_PIN, HIGH);
  delay(3000);
  digitalWrite(LOCK_PIN, LOW);
}

// -------------------------
// KEYPAD SETUP
// -------------------------
const byte ROWS = 4;
const byte COLS = 4;

char hexaKeys[ROWS][COLS] = {
  {'1','2','3','A'},
  {'4','5','6','B'},
  {'7','8','9','C'},
  {'*','0','#','D'}
};

byte rowPins[ROWS] = {2, 3, 4, 5};
byte colPins[COLS] = {6, 7, 8, 9};

Keypad keypad = Keypad(makeKeymap(hexaKeys), rowPins, colPins, ROWS, COLS);

// -------------------------
// TIME / SYNC
// -------------------------
unsigned long baseEpochMinutes = 0;
unsigned long baseMillis = 0;
bool synced = false;

// -------------------------
// 64-BIT ROTATIONS
// -------------------------
static inline uint64_t rotl64(uint64_t x, uint8_t r) {
  r &= 63;
  return (x << r) | (x >> (64 - r));
}

// -------------------------
// PIRATE CONSTANTS
// -------------------------
const uint64_t K1 = 0xC0B41E7ED15EA5E1ULL;
const uint64_t K2 = 0xBADC0FFEE0DDF00DULL;
const uint64_t K3 = 0x1EA57EADBEAD1DEAULL;
const uint64_t MOD_14 = 100000000000000ULL;

// -------------------------
// CODE GENERATOR
// -------------------------
static uint64_t generate_code_u64(unsigned long minutes32) {
  uint64_t m = (uint64_t)minutes32;
  uint64_t x = m ^ K1;
  x = x * K2;
  x = rotl64(x, 5);
  x = x + K3;
  return x % MOD_14;
}

void formatCode(unsigned long minutes32, char *buf) {
  uint64_t code = generate_code_u64(minutes32);
  for (int i = 13; i >= 0; i--) {
    buf[i] = '0' + (code % 10);
    code /= 10;
  }
  buf[14] = '\0';
}

// -------------------------
// GET CURRENT EPOCH MINUTES
// -------------------------
unsigned long getCurrentMinutes() {
  if (!synced) return baseEpochMinutes;
  unsigned long elapsed_ms = millis() - baseMillis;
  unsigned long elapsed_min = elapsed_ms / 60000UL;
  return baseEpochMinutes + elapsed_min;
}

// -------------------------
// OLED helpers (LOW RAM)
// -------------------------
static void oled_line1(const char *l1) {
  u8g2.firstPage();
  do {
    u8g2.setFont(u8g2_font_6x10_tf);
    u8g2.drawStr(0, 12, l1);
  } while (u8g2.nextPage());
}

static void oled_line2(const char *l1, const char *l2) {
  u8g2.firstPage();
  do {
    u8g2.setFont(u8g2_font_6x10_tf);
    u8g2.drawStr(0, 12, l1);
    u8g2.drawStr(0, 28, l2);
  } while (u8g2.nextPage());
}

// -------------------------
// READ CODE FROM KEYPAD
// -------------------------
bool getCodeFromKeypad(char *buf, size_t bufSize) {
  int len = 0;
  buf[0] = '\0';

  oled_line2("Insert code:", "");

  while (true) {
    char k = keypad.getKey();
    if (!k) continue;

    if (k == '*') {          // submit
      buf[len] = '\0';
      return true;
    }
    if (k == '#') {          // clear
      len = 0;
      Serial.println();
      Serial.print("Cleared. Enter code: ");

      buf[0] = '\0';
      oled_line2("Insert code:", "");
      continue;
    }
    if (k >= '0' && k <= '9') {
      if (len < (int)bufSize - 1) {
        buf[len++] = k;
        buf[len] = '\0';
        Serial.print(k);     // echo
        oled_line2("Insert code:", buf);
      }
    }
  }
}

// -------------------------
// SETUP
// -------------------------
void setup() {
  Serial.begin(115200);
  while (!Serial) { }

  pinMode(LOCK_PIN, OUTPUT);
  digitalWrite(LOCK_PIN, LOW);

  // OLED init (does NOT allocate 1KB RAM like Adafruit does)
  u8g2.begin();
  oled_line1("Time sync needed");

  Serial.println();
  Serial.println("=== PICO TREASURE ===");
  Serial.println("Send: T:<epoch_minutes> e.g. T:29433109");
  Serial.println();

  // wait for sync command
  while (!synced) {
    if (Serial.available()) {
      String line = Serial.readStringUntil('\n');
      line.trim();
      if (line.startsWith("T:")) {
        String value = line.substring(2);
        baseEpochMinutes = (unsigned long)value.toInt();
        baseMillis = millis();
        synced = true;

        Serial.print("Synced to epoch minutes = ");
        Serial.println(baseEpochMinutes);
        Serial.println("Press * on keypad to enter codes.");

        oled_line1("Press *");
      }
    }
  }
}

// -------------------------
// LOOP
// -------------------------
void loop() {
  char k = keypad.getKey();
  if (k != '*') {
    return;
  }

  char guess[32];
  Serial.print("Enter 14-digit code: ");
  getCodeFromKeypad(guess, sizeof(guess));

  Serial.println();
  Serial.print("You entered: ");
  Serial.println(guess);

  unsigned long minutes = getCurrentMinutes();
  Serial.print("Minutes now: ");
  Serial.println(minutes);

  char code[15], codePrev[15], codeNext[15];
  if (minutes > 0) {
    formatCode(minutes - 1, codePrev);
  } else {
    formatCode(0, codePrev);
  }
  formatCode(minutes, code);
  formatCode(minutes + 1, codeNext);

  Serial.print("Expected (t-1): ");
  Serial.println(codePrev);
  Serial.print("Expected  (t):  ");
  Serial.println(code);
  Serial.print("Expected (t+1): ");
  Serial.println(codeNext);

  bool correct =
    (strcmp(guess, code) == 0) ||
    (strcmp(guess, codePrev) == 0) ||
    (strcmp(guess, codeNext) == 0);

  Serial.print("Correct? ");
  Serial.println(correct ? "YES" : "NO");
  Serial.println();

  if (correct) {
    oled_line1("SUCCESS");
    Serial.print("Opening lock...");
    open_lock();
  } else {
    oled_line1("FAILURE");
  }

  delay(1200);
  oled_line1("Press *");
}
