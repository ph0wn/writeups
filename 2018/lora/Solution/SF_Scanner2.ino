#include <SPI.h>
#include <LoRa.h>

int SF = 10;
long lastSendTime = 0;        // last send time
int interval = 1000;          // interval between sends
int channel = 0; // Number of the LoRaWan channel
long freq[9] = {865E6,868.1E6,868.3E6,868.5E6,867.1E6, 867.3E6, 867.5E6,867.7E6,867.9E6};
long frequency = 867.1E6;

void setup() {
  Serial.begin(9600);

  
   
  while (!Serial);

  Serial.println("LoRa Receiver");

  if (!LoRa.begin(frequency)) {
    Serial.println("Starting LoRa failed!");
    while (1);
  }
}

void loop() {

if (millis() - lastSendTime > interval) {
    SF++;
  if(SF==13) {SF=7;
  
  LoRa.setFrequency(freq[channel]); // change frequency
  frequency = freq[channel];
  channel++;
  if(channel==9) {channel=0;}
    }
    
  LoRa.setSpreadingFactor(SF);
    lastSendTime = millis();            // timestamp the message
    interval =  2000;    // 2 seconds
  }

  
  // try to parse packet
  int packetSize = LoRa.parsePacket();
  if (packetSize) {
    // received a packet
    Serial.print("Received packet '");

    // read packet
    while (LoRa.available()) {
      Serial.print((char)LoRa.read());
    }

    // print RSSI of packet
    Serial.print("' with RSSI ");
    Serial.print(LoRa.packetRssi());

    // print SF of packet
    Serial.print("and with SF ");
    Serial.print(SF);
    // print freq of packet
    Serial.print(" at freq ");
    Serial.print(frequency);
    Serial.println("Hz");
  }
}
