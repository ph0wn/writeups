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

const char* wifi_ssid = "SpaPrivate";   
const char* wifi_password = "sp@_cha11en6e_secret"; 

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
  unlocked = false; // CENSOR IN PUBLIC DESCRIPTION
  calibrate_temp = 0; // CENSOR IN PUBLIC DESCRIPTION
  calibrate_hum = 0; // CENSOR IN PUBLIC DESCRIPTION
  memset(password, 0x00, BUFFER_LEN); // CENSOR IN PUBLIC DESCRIPTION
  Serial.println(F("[+] reset() done")); // CENSOR IN PUBLIC DESCRIPTION
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
    // get the flag: ph0wn{w0w_your_spa_is_hot++}
    Serial.println(F("Congrats! Here is your flag: ")); 
    Serial.print("p");
    Serial.print("h");
    Serial.print("0");
    Serial.print("w");
    Serial.print("n");
    Serial.print("{");
    Serial.print("w");
    Serial.print("0");
    Serial.print("w");
    Serial.print("_");
    Serial.print("y");
    Serial.print("o");
    Serial.print("u");
    Serial.print("r");
    Serial.print("_");
    Serial.print("s");
    Serial.print("p");
    Serial.print("a");
    Serial.print("_");
    Serial.print("i");
    Serial.print("s");
    Serial.print("_");
    Serial.print("h");
    Serial.print("o");
    Serial.print("t");
    Serial.print("+");
    Serial.print("+");
    Serial.println("}");
    server.send(200, "text/plain", F("Prepare your exploit and read the flag on serial port")); 
    reset();
  } else {
    char json[50];
    sprintf(json, "{\"temperature\": %.1f, \"humidity\" : %.0f }",  temperatureC, humidity);
    server.send(200, "text/json", json);
  }
}

void unlock() {
  char secret[BUFFER_LEN]; // "WeWontGiveThisP"
  memset(&secret[0], int("W"), sizeof(char));
  memset(&secret[1], int("e"), sizeof(char)); 
  memset(&secret[2], int("W"), sizeof(char));
  memset(&secret[3], int("o"), sizeof(char));
  memset(&secret[4], int("n"), sizeof(char));
  memset(&secret[5], int("t"), sizeof(char));
  memset(&secret[6], int("G"), sizeof(char));
  memset(&secret[7], int("i"), sizeof(char));
  memset(&secret[8], int("v"), sizeof(char));
  memset(&secret[9], int("e"), sizeof(char));
  memset(&secret[10], int("T"), sizeof(char));
  memset(&secret[11], int("h"), sizeof(char));
  memset(&secret[12], int("i"), sizeof(char));
  memset(&secret[13], int("s"), sizeof(char));
  memset(&secret[14], int("P"), sizeof(char));

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
