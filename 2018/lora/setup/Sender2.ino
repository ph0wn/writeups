#include <SPI.h>
#include <LoRa.h>

int counter = 0;

void setup() {
  Serial.begin(9600);
  
  while (!Serial);

  Serial.println("LoRa Sender");

  if (!LoRa.begin(867.2E6)) {
    Serial.println("Starting LoRa failed!");
    while (1);
  }
}

void loop() {
  LoRa.setSpreadingFactor(8); 
  Serial.print("Sending packet: ");
  Serial.println(counter);

  // send packet
  LoRa.beginPacket();
  LoRa.print("phown{gHS74dR120%+}");
  //LoRa.print(counter);
  LoRa.endPacket();

  counter++;

  delay(500);
}
