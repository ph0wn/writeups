#include <ESP8266WiFi.h>
#include <ESP8266WebServer.h>
#include <DHTesp.h>
#define DHTPIN 4
#define DHTTYPE DHT11 // Blue sensor with humidity

#ifdef ESP32
#pragma message(THIS CODE IS FOR ESP8266 ONLY!)
#error Select ESP8266 board.
#endif

// REQUIRES the following Arduino libraries:
// https://github.com/beegee-tokyo/DHTesp
// Use: LOLIN(WEMOS) D& R2 & mini

const char* wifi_ssid = "CENSORED";   
const char* wifi_password = "CENSORED"; 

#define HTTP_REST_PORT 8080
ESP8266WebServer server(HTTP_REST_PORT);

DHTesp dht;
WiFiClient espClient;


// current temperature
float temperatureC;  
float humidity;      
// calibration value
float calibrate_temp;
float calibrate_hum; 
// restricted operations
#define BUFFER_LEN 16
bool unlocked = false;
char password[BUFFER_LEN];

void reset() { 
// CENSORED
}

void getData() {
  // get raw temperature and  humidity values
  delay(dht.getMinimumSamplingPeriod());
  float rawC = dht.getTemperature();
  float rawH = dht.getHumidity();

  // adjust values with calibration data
  temperatureC = rawC + calibrate_temp;
  humidity = rawH + calibrate_hum;

  if (temperatureC > 60 && humidity > 100) {
    // get the flag
    Serial.println(F("Congrats! Here is your flag: "));
    // CENSORED FLAG :)
    server.send(200, "text/plain", F("Prepare your exploit and read the flag on serial port")); 
    reset();
  } else {
    char json[50];
    sprintf(json, "{\"temperature\": %.1f, \"humidity\" : %.0f }",  temperatureC, humidity);
    server.send(200, "text/json", json);
  }
}

void unlock() {
  char secret[BUFFER_LEN];
  // CENSORED SECRET
  String tmp_password = server.arg("pwd");
  tmp_password.toCharArray(password, tmp_password.length()+1);

  if (strncmp(password, secret, BUFFER_LEN-1) == 0) {
    Serial.println(F("Correct password!"));
    unlocked = true;
  }
}

void getDebug() {
  char line[100];
  sprintf(line, "password addr= %08x value=%s\nunlocked addr= %08x value=%d\n", password, password, &unlocked, unlocked);
  return server.send(200, "text/plain", line);
}


void checkArguments() {
  if (! server.hasArg("pwd") ) {
    server.send(401, "text/plain", F("Missing pwd"));
  }
  
  if (! server.hasArg("value") ) {
    return server.send(400, "text/plain", F("Missing calibration value"));
  }
}

// calibration is a restricted operation which requires credentials
void calibrate(bool temp) {
  checkArguments();
  unlock();
  if (! unlocked) {
    return server.send(401, "text/plain", F("Not authorized"));
  }
  String calibration = server.arg("value");
  Serial.print(F("[+] calibrate")); 
  if (temp) {
    Serial.print(F("_temp="));
    calibrate_temp = calibration.toFloat();
    Serial.println(calibrate_temp);  
    server.send(200, "text/plain", "Temperature calibration done");
  } else {
    Serial.print(F("_hum="));
    calibrate_hum = calibration.toFloat();
    Serial.println(calibrate_hum);  
    server.send(200, "text/plain", "Humidity calibration done");
  }
}

void calibrateTemp() {
  calibrate(true);
}

void calibrateHum() {
  calibrate(false);
}
 
void restServerRouting() {
    server.on("/", HTTP_GET, []() {
        server.send(200, F("text/html"),
            F("Ph0wn DHT11 REST Web Server"));
    });
    server.on(F("/data"), HTTP_GET, getData);
    server.on(F("/calibrate/temperature"), HTTP_GET, calibrateTemp);
    server.on(F("/calibrate/humidity"), HTTP_GET, calibrateHum);
    server.on(F("/debug"), HTTP_GET, getDebug);
}

void handleNotFound() {
  server.send(404, "text/plain", F("Not Found"));
}

void setup() {
  // this code will only run once
  Serial.begin(115200);

  Serial.println(F("Setting up DHT11..."));
  dht.setup(D5, DHTesp::DHT11);

  // connect to wifi
  Serial.print(F("Setting up Wifi..."));
  WiFi.begin(wifi_ssid, wifi_password); 
  connect_wifi();

  // REST Server setup
  restServerRouting();
  server.onNotFound(handleNotFound);
  server.begin();
  Serial.print(F("[+] HTTP REST server started on port "));
  Serial.println(HTTP_REST_PORT);
}

void connect_wifi() {
   // Wait for connection
   while (WiFi.status() != WL_CONNECTED) {
     delay(800);
     Serial.print(F("."));
   }
   Serial.println("");
   Serial.print(F("[+] Connected to SSID: ")); Serial.println(wifi_ssid);
   Serial.print(F("[+] IP address: ")); Serial.println(WiFi.localIP());
}

void loop() {
  server.handleClient();
}
