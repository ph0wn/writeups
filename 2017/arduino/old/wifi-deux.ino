#include <WiFi.h>

char ssid[] = "WifiSSID";     //  your network SSID (name)
  
char minuscule[] = "abcdefghijklmnopqrstuvwxyz";
char majuscule[] = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
char chiffre[] = "0123456789";
char test[] ="                                                                                                                                         ";
int status = WL_IDLE_STATUS;     // the Wifi radio's status

void setup() {

  char pass[12 + 1]; // your network password

  // initialize serial:
  Serial.begin(9600);

  // attempt to connect using WPA2 encryption:
  Serial.println("Attempting to connect to WPA network...");

  //Concat pass
  pass[0] = minuscule[20 - 1] ;
  pass[1] = minuscule[8 - 1] ;
  pass[2] = minuscule[5 - 1] ;
  pass[3] = majuscule[8 - 1] ;
  pass[4] = minuscule[9 - 1] ;
  pass[5] = minuscule[4 - 1] ;
  pass[6] = minuscule[4 - 1] ;
  pass[7] = minuscule[5 - 1] ;
  pass[8] = minuscule[14 - 1] ;
  pass[9] = majuscule[11 - 1] ;
  pass[10] = minuscule[5 - 1] ;
  pass[11] = minuscule[25 - 1] ;
  pass[12] = '\0';
  
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
 
