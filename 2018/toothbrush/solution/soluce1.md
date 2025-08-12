- Disassemble the Android application
- Look for class Cipher as the events are said to be encrypted

We see some in:

- ./smali/com/squareup/okhttp/ConnectionSpec.smali: this is for HTTP communication
- ./smali/com/squareup/okhttp/internal/huc/DelegatingHttpsURLConnection.smali: same
- ./smali/com/squareup/okhttp/Handshake.smali: same
- ./smali/com/squareup/okhttp/CipherSuite.smali: same
- ./smali/dda.smali: calls okhttp CipherSuite
- ./smali/dna.smali: **interesting**

In class `dna`, we have for example:

```java
protected dna() {
        super();
        try {
            this.c = new SecretKeySpec(dnf.a("e02b90e8e50be5b001c299a5039462c2"), "AES");
            this.b = Cipher.getInstance("AES/ECB/NoPadding");
            dna.a = this;
            return;
        }
        catch(NoSuchPaddingException v0) {
        }
        catch(NoSuchAlgorithmException v0_1) {
        }

        ((GeneralSecurityException)v0_1).printStackTrace();
    }
```

We obviously see:

- a constructor that initializes the key
- a method `a(byte[])` which encrypts a buffer
- a method `b(byte[])` which decrypts a buffer

Who uses method b?

- dlv.g: returns a brush event! That's exactly what we are looking for
- dlv.o: less interesting

So our key is `e02b90e8e50be5b001c299a5039462c2` and the algorithm is `AES/ECB/NoPadding`

Flag: `ph0wn{e02b90e8e50be5b001c299a5039462c2}`
