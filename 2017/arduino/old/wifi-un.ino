#include <WiFi.h>

char ssid1[] = "Wifi";     
char passUn[] = "theHid";
char ssid2[] = "SSID";
char passDeux[] = "denKey";    

int status = WL_IDLE_STATUS;     // the Wifi radio's status

void setup() {
  char ssid[strlen(ssid1) + strlen(ssid2) + 1]; //  your network SSID (name)
  char pass[strlen(passUn) + strlen(passDeux) + 1]; // your network password

  // initialize serial:
  Serial.begin(9600);

  // attempt to connect using WPA2 encryption:
  Serial.println("Attempting to connect to WPA network...");

  //Concat SSID
  for (int i = 0 ; i < strlen(ssid1); i++) {
    ssid[i] = ssid1[i];
  }
  for (int i = 0 ; i < strlen(ssid2) + 1; i++) {
    ssid[strlen(ssid1) + i] = ssid2[i];
  } 

  //Concat pass
  for (int i = 0 ; i < strlen(passUn); i++) {
    pass[i] = passUn[i];
  }
  for (int i = 0 ; i < strlen(passDeux) + 1; i++) {
    pass[strlen(passUn) + i] = passDeux[i];
  } 
  

  status = WiFi.begin(ssid, pass);

  // if you're not connected, stop here:
  if ( status != WL_CONNECTED) {
    Serial.println("Couldn't get a wifi connection");
    while(true);
  }
  // if you are connected, print out info about the connection:
  else {
    Serial.println("Connected to network");
  }
}

void loop() {
  // do nothing
}
 
