/*
    Based on Neil Kolban example for IDF: https://github.com/nkolban/esp32-snippets/blob/master/cpp_utils/tests/BLE%20Tests/SampleWrite.cpp
    Ported to Arduino ESP32 by Evandro Copercini
*/

#include <BLEDevice.h>
#include <BLEUtils.h>
#include <BLEServer.h>
#include <string.h>

#define SERVICE_FLAG_UUID        "deadbeef-ff11-aadd-0000-000100000001"
#define CHARACTERISTIC_FLAG_UUID "deadbeef-ff11-aadd-0000-000100000002"

#define SERVICE_RENPHOFIT_UUID        "00005301-0000-0041-4c50-574953450000"
#define CHARACTERISTIC_RENPHOFIT_WRITE_UUID "00005302-0000-0041-4c50-574953450000"
#define CHARACTERISTIC_RENPHOFIT_READ_UUID  "00005303-0000-0041-4c50-574953450000"
#define RENPHOFIT_READ_VALUE "Pico le Croco says you must jump"

#define SERVICE_OTA_UUID        "f000ffc0-0451-4000-b000-000000000000"
#define CHARACTERISTIC_OTA1_UUID "f000ffc1-0451-4000-b000-000000000000"
#define CHARACTERISTIC_OTA2_UUID "f000ffc2-0451-4000-b000-000000000000"

#define SERVICE_GENERIC_ATT_PROFILE_UUID "00001801-0000-1000-8000-00805f9b34fb"
#define CHARACTERISTIC_DEVICE_NAME_UUID "00002a00-0000-1000-8000-00805f9b34fb"
#define DEVICE_NAME_VALUE "PH0WN-VALIDATION-ROPE #03"

#define SERVICE_DEVICE_INFORMATION_UUID "0000180a-0000-1000-8000-00805f9b34fb"
#define CHARACTERISTIC_MANUFACTURER_NAME_UUID "00002a29-0000-1000-8000-00805f9b34fb"
#define DEVICE_MANUFACTURER_VALUE "BEKEN SAS"
#define CHARACTERISTIC_MODEL_NUMBER_UUID "00002a24-0000-1000-8000-00805f9b34fb"
#define MODEL_NUMBER_VALUE "BK-BLE-1.0"
#define CHARACTERISTIC_SERIAL_NUMBER_UUID "00002a25-0000-1000-8000-00805f9b34fb"
#define SERIAL_NUMBER_VALUE "1.3.3.7-ph0wn"
#define CHARACTERISTIC_HARDWARE_REVISION_UUID "00002a27-0000-1000-8000-00805f9b34fb"
#define CHARACTERISTIC_FIRMWARE_REVISION_UUID "00002a26-0000-1000-8000-00805f9b34fb"
#define CHARACTERISTIC_SOFTWARE_REVISION_UUID "00002a28-0000-1000-8000-00805f9b34fb"
#define CHARACTERISTIC_SYSTEM_ID_UUID "00002a23-0000-1000-8000-00805f9b34fb"

#define NO_FLAG "No flag for lame hackers -- says Pico le Croco"
#define INVERSE_TEXT "Kudos from Pico"
#define DEBUG false

#include <SPI.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

#define SCREEN_WIDTH 128 // OLED display width, in pixels
#define SCREEN_HEIGHT 64 // OLED display height, in pixels

// Declaration for an SSD1306 display connected to I2C (SDA, SCL pins)
// The pins for I2C are defined by the Wire-library.
// On an arduino UNO:       A4(SDA), A5(SCL)
// On an arduino MEGA 2560: 20(SDA), 21(SCL)
// On an arduino LEONARDO:   2(SDA),  3(SCL), ...
#define OLED_RESET     -1 // Reset pin # (or -1 if sharing Arduino reset pin)
#define SCREEN_ADDRESS 0x3C ///< See datasheet for Address; 0x3D for 128x64, 0x3C for 128x32
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);


char *flag = "ph0wn{weSeeYouRF1t_GooD} -- please disconnect now";
BLECharacteristic *pCharFlag;
BLECharacteristic *pCharRenpho;
BLEAdvertising *pAdvertising;
bool connected = false;

class serverCallback: public BLEServerCallbacks {
  void onConnect(BLEServer *pServer) {
    Serial.println("[+] onConnect: erasing flag");
    pCharFlag->setValue(NO_FLAG);
    connected = true;
  }
  void onDisconnect(BLEServer *pServer) {
    Serial.println("[+] onDisconnect: erasing flag");
    pCharFlag->setValue(NO_FLAG);
    connected = false;
  }
};

class cmdCallback: public BLECharacteristicCallbacks {
  void onWrite(BLECharacteristic *pCharacteristic) {
    std::string value = pCharacteristic->getValue();
    if (DEBUG == true) {
      Serial.printf("onWrite(): Input len: %d\n", value.length());
    }

    /* JUMP 1337 */
    char expected_cmd[] = { 0x02, 0x00, 0x05, 0x81, 0x00, 0x00, 0x05, 0x39, 0xdb, 0x3e };
    
    if (value.length() != sizeof(expected_cmd)) {
      pCharFlag->setValue(NO_FLAG);
      if (DEBUG == true)
          Serial.println("[-] Incorrect length");
      return;
    }
    
    if (DEBUG == true) {
      Serial.println("[+] Length is correct");
    }
    
    /* compare value - memcmp doesnt work on std::string */
    bool ok = true;
    int i = 0;
    for (i=0; i< value.length(); i++) {
       if (value[i] != expected_cmd[i]) {
         if (DEBUG == true) {
           Serial.printf("[-] Incorrect value[%d]=%02x\n", i, value[i]);
         }
         ok = false;
         break;
       }
     }
     if (ok == true) {
       pCharFlag->setValue(flag);
       if (DEBUG == true)
         Serial.println("FLAG ! ");
     } else {
        pCharFlag->setValue(NO_FLAG);
        if (DEBUG == true) Serial.println("ERROR !");
     }
  }
    
};


void setup() {
  Serial.begin(115200);
  Serial.println("Ph0wn Jump Rope Validation Server -- v1.15");

  BLEDevice::init(DEVICE_NAME_VALUE);
  BLEServer *pServer = BLEDevice::createServer();
  pServer->setCallbacks(new serverCallback());
  Serial.println("[+] Server created");

  BLEService *pServiceFlag = pServer->createService(SERVICE_FLAG_UUID);
  pCharFlag = pServiceFlag->createCharacteristic(CHARACTERISTIC_FLAG_UUID, BLECharacteristic::PROPERTY_READ);
  pCharFlag->setValue(NO_FLAG);
  
  if (DEBUG == true) Serial.println("[+] Flag service created");
  
  BLEService *pServiceRenpho = pServer->createService(SERVICE_RENPHOFIT_UUID);
  pCharRenpho = pServiceRenpho->createCharacteristic(CHARACTERISTIC_RENPHOFIT_WRITE_UUID, BLECharacteristic::PROPERTY_WRITE);
  pCharRenpho->setCallbacks(new cmdCallback());
  BLECharacteristic *pRead = pServiceRenpho->createCharacteristic(CHARACTERISTIC_RENPHOFIT_READ_UUID, BLECharacteristic::PROPERTY_READ| BLECharacteristic::PROPERTY_NOTIFY);
  pRead->setValue(RENPHOFIT_READ_VALUE);
  if (DEBUG == true) Serial.println("[+] Renpho service created");
  
  BLEService *pServiceDeviceInfo = pServer->createService(SERVICE_DEVICE_INFORMATION_UUID);
  BLECharacteristic *pManufacturer = pServiceDeviceInfo->createCharacteristic(CHARACTERISTIC_MANUFACTURER_NAME_UUID, BLECharacteristic::PROPERTY_READ);
  pManufacturer->setValue(DEVICE_MANUFACTURER_VALUE);
  BLECharacteristic *pModel = pServiceDeviceInfo->createCharacteristic(CHARACTERISTIC_MODEL_NUMBER_UUID, BLECharacteristic::PROPERTY_READ);
  pModel->setValue(MODEL_NUMBER_VALUE);
  BLECharacteristic *pSerial = pServiceDeviceInfo->createCharacteristic(CHARACTERISTIC_SERIAL_NUMBER_UUID, BLECharacteristic::PROPERTY_READ);
  pSerial->setValue(SERIAL_NUMBER_VALUE);
  BLECharacteristic *pHardware = pServiceDeviceInfo->createCharacteristic(CHARACTERISTIC_HARDWARE_REVISION_UUID, BLECharacteristic::PROPERTY_READ);
  pHardware->setValue(SERIAL_NUMBER_VALUE);
  BLECharacteristic *pFirmware = pServiceDeviceInfo->createCharacteristic(CHARACTERISTIC_FIRMWARE_REVISION_UUID, BLECharacteristic::PROPERTY_READ);
  pFirmware->setValue(SERIAL_NUMBER_VALUE);
  BLECharacteristic *pSoftware = pServiceDeviceInfo->createCharacteristic(CHARACTERISTIC_SOFTWARE_REVISION_UUID, BLECharacteristic::PROPERTY_READ);
  pSoftware->setValue(SERIAL_NUMBER_VALUE);
  BLECharacteristic *pSystemId = pServiceDeviceInfo->createCharacteristic(CHARACTERISTIC_SYSTEM_ID_UUID, BLECharacteristic::PROPERTY_READ);
  pSystemId->setValue(SERIAL_NUMBER_VALUE);
  if (DEBUG == true) Serial.println("[+] Device info service created");

  BLEService *pServiceOta = pServer->createService(SERVICE_OTA_UUID);
  BLECharacteristic *pOta1 = pServiceOta->createCharacteristic(CHARACTERISTIC_OTA1_UUID, BLECharacteristic::PROPERTY_WRITE | BLECharacteristic::PROPERTY_NOTIFY);
  BLECharacteristic *pOta2 = pServiceOta->createCharacteristic(CHARACTERISTIC_OTA2_UUID, BLECharacteristic::PROPERTY_WRITE | BLECharacteristic::PROPERTY_NOTIFY);
  //pOtaR->setValue(SERIAL_NUMBER_VALUE);
  //pOtaW->setCallbacks(new otaCallback());
  if (DEBUG == true) Serial.println("[+] OTA service created");
    
  pServiceFlag->start();
  pServiceOta->start();
  pServiceRenpho->start();
  pServiceDeviceInfo->start();
  Serial.println("[+] Services started");
  
  pAdvertising = pServer->getAdvertising();
  Serial.println("[+] Advertising initialized");
  
  //le "Wire" au début, c'est, un hack crado: je n'ai pas trouvé comment faire propre le mapping de la lib et des 2 fils de l'I2C, donc j'ai fait bas-niveau, ce n'est pas autorisé normalement mais ça marche comme un charme
  Wire.begin(5, 4);
  // SSD1306_SWITCHCAPVCC = generate display voltage from 3.3V internally
  if (!display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
    Serial.println(F("SSD1306 allocation failed"));
    for (;;); // Don't proceed, loop forever
  }

  // Show initial display buffer contents on the screen --
  // the library initializes this with an Adafruit splash screen.
  display.display();
  delay(500); // Pause for 2 seconds

  // Clear the buffer
  display.clearDisplay();
  display.display();
  display.setCursor(0, 0);
  display.setTextSize(2);             // Draw 2X-scale text
  display.setTextColor(SSD1306_WHITE);
  display.print(F(DEVICE_NAME_VALUE));

  display.setCursor(0, 50);
  display.setTextColor(SSD1306_BLACK, SSD1306_WHITE); // Draw 'inverse' text
  display.setTextSize(1);
  display.print(F(INVERSE_TEXT));
  display.display();
}

void loop() {
  // enforcing a single client at a time
  if (connected == true) {
    pAdvertising->stop();
  } else {
    pAdvertising->start();
  }
  // wait
  delay(1000);
}
