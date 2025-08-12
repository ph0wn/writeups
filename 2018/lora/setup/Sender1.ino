#include <SPI.h>
#include <LoRa.h>

int counter = 0;

void setup() {
  Serial.begin(9600);
  
  while (!Serial);

  Serial.println("LoRa Sender");

  if (!LoRa.begin(865E6)) {
    Serial.println("Starting LoRa failed!");
    while (1);
  }
}

void loop() {
  LoRa.setSpreadingFactor(10); 
  Serial.print("Sending packet: ");
  Serial.println(counter);

  // send packet
  LoRa.beginPacket();
  LoRa.print("ph0wn{5UsP02cY48!%}");
  //LoRa.print(counter);
  LoRa.endPacket();

  counter++;

  delay(500);
}
