#include <LiquidCrystal.h>

const int irReceiverStartPin = 6;  // First IR receiver to start the timer
const int irReceiverStopPin = 7;   // Second IR receiver to stop the timer
unsigned long startTime = 0;       // Variable to store the start time
bool timerRunning = false;         // Track if the timer is currently running
LiquidCrystal lcd(13, 12, 8, 9, 10, 11);

void setup() {
  pinMode(irReceiverStartPin, INPUT);  // Set start IR receiver pin as input
  pinMode(irReceiverStopPin, INPUT);   // Set stop IR receiver pin as input
  lcd.begin(16, 2);    
  Serial.begin(9600); 
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Waiting to start!");
}

void loop() {  
  // Check if the first IR receiver is interrupted to start the timer
  if (!timerRunning && digitalRead(irReceiverStartPin) == LOW) {
    startTime = millis();            // Record start time
    timerRunning = true;             // Set timer as running
    Serial.println("First interruption detected on IR receiver 1, timer started.");
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("Race started!");

  }

  // Check if the second IR receiver is interrupted to stop the timer
  if (timerRunning && digitalRead(irReceiverStopPin) == LOW) {
    unsigned long endTime = millis();         // Record end time
    unsigned long timeDifference = endTime - startTime; // Calculate elapsed time
    float seconds = timeDifference / 1000.0;
    // Print the time difference
    Serial.print("Second interruption detected on IR receiver 2. Time between interruptions: ");
    Serial.print(timeDifference);
    Serial.println(" ms");
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("Your time was");
    lcd.setCursor(0, 1);  // Print a message to the LCD
    lcd.print(seconds, 4);
    lcd.print(" s");


    // Reset timer for next measurement
    timerRunning = false;
    Serial.println("Waiting for first interruption on IR receiver 1 to start timer...");
    delay(60000);
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("Waiting to start!");
  }
}
