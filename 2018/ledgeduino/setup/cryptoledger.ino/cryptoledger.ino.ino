int timer = 8000;

void setup() {
  // put your setup code here, to run once:
  Serial.begin(9600);
  Serial.println(F("--------------------------------------------------"));
  Serial.println(F("Ledgeduino - Crypo Ledger v0.1 for Arduino - Ph0wn"));
  Serial.println(F("--------------------------------------------------"));
  Serial.println(" ");
}

void loop() {
  // put your main code here, to run repeatedly:
  Serial.println(F("Hello, I am Ledgeduino, the ledger that protects your cryptocurrencies with RSA 2048 OAEP"));

  // this is the encrypted flag    
  Serial.println(F("===== SAFE ============"));
  Serial.println(F("a81c3c9d573ac2863a040051fc8abe40123649590f7400ffd392c9080373ac25a629c650968f22533e4f52e570940e7268646f84f7323b4c41bda39fd875b29ad4e5795acb9cc8795f9c4982fffbb989dcdf886390752d5169825573b3d8d13ca2efc2515d0131f2698081b8fe3862a07e06e89c08b2e8725176c6d387f0100ef65a401b6d2c21ff1731e2aea13eb9d8693884a50ffb89373485548a266a5236b429eabfe5bcfbcd7c3ade843c994e7b79e7ba4481e8228fb8e3cdfaac33a58283f52edc62144a735ef2005638cbcecf2b491401476af9f641321ce96ea84788f2bd3dc2f9dc391a4247be32ce25f1d237a0b618b8ab41906de7aff50d42c35f"));

  Serial.println(F("===== BALANCE ========="));
  Serial.println(F("Retrieving your current balance..."));
  delay(1000);
  Serial.println(F("Your balance: 423.15 BTC"));
  Serial.println(F("Computing Chinese Remainder Theorem"));
  Serial.println(F("Checking RSA-SHA256-PKCS1_5 signature..."));
  delay(2000);
  
  Serial.println(F("ERROR!!!"));
  Serial.println(F("YOUR LEDGER IS COMPROMISED"));
  Serial.println(F("We apologize, your balance may be incorrect ;)"));
  Serial.println(" ");
  Serial.println(F("Debug info: "));
  Serial.println(F("  Message: 'Your balance: 423.15 BTC'"));
  Serial.println(F("  Incorrect signature: 11e8b022452ab62d73f486f4e791acbc5935f8b3fa93f5662c1371d40201b7aaade2269c6de72ca7fb5f19fe0f1c7126df0224838c1e228f71798032f246cfb22e3926c5b1682c5f066f79cc6f17e998cefdfc24e5ac8b4201965af18d3932065c52e94129c2d2e924f65c2a66e22644e0c35a24abae004efd45e705ca7b049a6ccb46cefdce6fd825b0339b5d7c883a3690349301c112400eeb27bce7932e56147c3a0c1ed19b2c65f0dcdb7f135c98c98129146ec709508b691f724b1a498fb71ad4dd8267413f0f846cfc6900803d53acb0c0fb071dbd79414d249eb30f7df6d4a2bec911ac76623ea547a6cac36b2f22fe9d5ce6b8bc592b638e7bf297c7"));
  Serial.println(F("  Public key: "));
  Serial.println(F("-----BEGIN PUBLIC KEY-----"));
  Serial.println(F("MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA3g8RQ9p2sA5AQIdG9vk/"));
  Serial.println(F("Ke5JsFhJa+Kd7brE2FO59n6PC7WPWS0zhA+INvChao2p9AsZ/RYTTlyqGfCY4MCY"));
  Serial.println(F("TygA9j55gzl0FasGU3HJRtPefAr207INTDVRjOwVTmpXHjHP9itTKsWrMTze6yfI"));
  Serial.println(F("YY/fxpdxWRHjf6Od8SphGGz3ZtwQulGS32uDXM+sy9fpmnLqaa7rnFRAymKKy9bL"));
  Serial.println(F("IiFJomlMvqZFmNy+zn1Z2uTRZlxshXMLE/T3Glucp/ySbRgdscRZYrskiY1SJspf"));
  Serial.println(F("MesS7SF6zYQ8CSPVQfFbipu7CGErZbxlPH9DfLtUpnC2HSGEdfXS0ZRaUqNyX1cV"));
  Serial.println(F("NQIDAQAB"));
  Serial.println(F("-----END PUBLIC KEY-----")); 
  Serial.println(" ");
      
  delay(timer);
}
