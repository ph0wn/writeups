#include <LiquidCrystal.h>

// Pin definitions for LCD
LiquidCrystal lcd(13, 12, 8, 9, 10, 11);

// Pin definitions for LEDs and IR sensors
const int irSensor1 = 6;     // IR receiver 1 connected to pin 6
const int irSensor2 = 7;     // IR receiver 2 connected to pin 7

void setup() {
  // Initialize the LCD
  lcd.begin(16, 2); // LCD has 16 columns and 2 rows
  
  // Set IR sensor pins as input
  pinMode(irSensor1, INPUT);
  pinMode(irSensor2, INPUT);

  // Display initial message
  lcd.setCursor(0, 0);
  lcd.print("Start: Unknown");
  lcd.setCursor(0, 1);
  lcd.print("End: Unknown");
}

void loop() {
  // Read IR sensor states
  int sensor1State = digitalRead(irSensor1);
  int sensor2State = digitalRead(irSensor2);

  // IR sensor 1
  if (sensor1State == HIGH) {
    lcd.setCursor(7, 0);
    lcd.print("Beam OK  "); // "Beam OK" and padding spaces to clear old data
  } else {
    lcd.setCursor(7, 0);
    lcd.print("Broken   "); // "Broken" and padding spaces to clear old data
  }

  // IR sensor 2
  if (sensor2State == HIGH) {
    lcd.setCursor(5, 1);
    lcd.print("Beam OK  "); // "Beam OK" and padding spaces to clear old data
  } else {
    lcd.setCursor(5, 1);
    lcd.print("Broken   "); // "Broken" and padding spaces to clear old data
  }
}
