// Define pins
const int irSensorPowerPin = 3; // Pin to power the IR receiver
const int irSensorPin = 2;      // Pin connected to the IR sensor output

void setup() {
  // Set up the power pin as an output
  pinMode(irSensorPowerPin, OUTPUT);
  digitalWrite(irSensorPowerPin, HIGH); // Power the IR sensor by setting the pin to HIGH

  // Set up the sensor pin as an input
  pinMode(irSensorPin, INPUT);

  // Begin serial communication
  Serial.begin(9600);
  Serial.println("IR Break Beam Sensor Test with External Power");
}

void loop() {
  // Read the state of the IR sensor
  int sensorState = digitalRead(irSensorPin);

  // Check if the sensor is interrupted
  if (sensorState == LOW) {
    Serial.println("Beam Interrupted");
  } else {
    Serial.println("Beam Clear");
  }

  // Add a small delay for readability in the serial monitor
  delay(1000);
}
