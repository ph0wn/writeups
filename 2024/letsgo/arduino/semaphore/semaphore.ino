// Define pins
const int irSensorPowerPin = 2;    // Pin for the switch
const int switchPin = 3;    // Pin for the switch
const int outputPin = 4;    // Pin for output (5V or 0V)

// Variables for timing
unsigned long randomDelay1 = 0;
unsigned long randomDelay2 = 0;

void setup() {
  // Light up the sensor close to the light
  pinMode(irSensorPowerPin, OUTPUT);
  digitalWrite(irSensorPowerPin, HIGH); // Power the IR sensor by setting the pin to HIGH
  
  pinMode(switchPin, INPUT_PULLUP);  // Set switch pin as input with internal pull-up resistor
  pinMode(outputPin, OUTPUT);        // Set output pin as output
  digitalWrite(outputPin, LOW);      // Initialize output to 0V (LOW)

  // Initialize serial communication at 9600 bits per second:
  Serial.begin(9600);
  Serial.println("System Initialized.");
}

void loop() {
  // Check if switch is closed (active low since we used INPUT_PULLUP)
  if (digitalRead(switchPin) == LOW) {
    Serial.println("Switch closed. Starting sequence...");

    // Wait for a random time between 2 and 10 seconds
    randomDelay1 = random(2000, 10000);
    Serial.print("Waiting for ");
    Serial.print(randomDelay1 / 1000.0);
    Serial.println(" seconds before sending 5V output...");
    delay(randomDelay1);

    // Set output pin to 5V (HIGH)
    digitalWrite(outputPin, HIGH);
    Serial.println("5V output sent.");

    // Wait for another random time between 2 and 10 seconds
    randomDelay2 = random(2000, 10000);
    Serial.print("Waiting for ");
    Serial.print(randomDelay2 / 1000.0);
    Serial.println(" seconds before sending 0V output...");
    delay(randomDelay2);

    // Set output pin to 0V (LOW)
    digitalWrite(outputPin, LOW);
    Serial.println("0V output sent.");

    // Wait for 1 second
    Serial.println("Waiting 2 second before sending 5V output again...");
    delay(2000);

    // Set output pin to 5V (HIGH) again
    digitalWrite(outputPin, HIGH);
    Serial.println("5V output sent again.");
 
  // Wait for 20 seconds before resetting the output to LOW
    Serial.println("Waiting 20 seconds before resetting to 0V output...");
    delay(20000);

  // Flash to signal restarting
  digitalWrite(outputPin, LOW);
  delay(200);
  digitalWrite(outputPin, HIGH);
  delay(200);
  digitalWrite(outputPin, LOW);
  delay(200);
  digitalWrite(outputPin, HIGH);
  delay(200);
  digitalWrite(outputPin, LOW);
  delay(200);
  digitalWrite(outputPin, HIGH);
  delay(200);
  digitalWrite(outputPin, LOW);
  delay(200);
  digitalWrite(outputPin, HIGH);
  delay(200);
  digitalWrite(outputPin, LOW);
  delay(200);
  digitalWrite(outputPin, HIGH);
  delay(200);
  digitalWrite(outputPin, LOW);
  delay(200);
  digitalWrite(outputPin, HIGH);
  delay(200);
  digitalWrite(outputPin, LOW);
  Serial.println("Ready for new run");
  }
  delay(10);
}
