import javax.crypto.SecretKey;
import javax.crypto.SecretKeyFactory;
import javax.crypto.Cipher;
import javax.crypto.IllegalBlockSizeException;
import javax.crypto.NoSuchPaddingException;
import java.io.UnsupportedEncodingException;
import javax.crypto.BadPaddingException;
import java.security.SecureRandom;
import java.security.AlgorithmParameters;
import java.security.NoSuchAlgorithmException;
import java.security.InvalidKeyException;
import java.security.InvalidAlgorithmParameterException;
import java.security.spec.KeySpec;
import java.security.spec.InvalidKeySpecException;
import java.security.spec.InvalidParameterSpecException;
import javax.crypto.spec.PBEKeySpec;
import javax.crypto.spec.IvParameterSpec;
import javax.crypto.spec.SecretKeySpec;

public class Mycrypto {
    static final int ITERATIONS = 1000;
    static final int KEY_LENGTH = 128;
    static final int SALT_LENGTH = 64;
    String plaintext = "This is a test Blah blah:wow";
    String passphrase = "Crocodiles are green";
    private byte [] salt;
    private byte [] iv;

    public static void displayArray(byte [] array) {
	int i;
	for (i=0; i< array.length; i++) {
	    System.out.printf("%02x ", array[i]);
	}
	System.out.println("");
    }

    public Mycrypto() throws NoSuchAlgorithmException {
	System.out.println("Object creation");
	SecureRandom sr = SecureRandom.getInstance("SHA1PRNG");
	this.salt = new byte[SALT_LENGTH];
	sr.nextBytes(this.salt);
	System.out.printf("  Salt array (length=%d)\n", this.salt.length);
	displayArray(this.salt);
    }
    
    public String decrypt(byte [] ciphertext) throws NoSuchAlgorithmException, InvalidKeyException, IllegalBlockSizeException, NoSuchPaddingException, InvalidKeySpecException, InvalidAlgorithmParameterException, BadPaddingException, UnsupportedEncodingException {
	SecretKey secretKey = generateKey(this.passphrase, this.salt, ITERATIONS, KEY_LENGTH);
	Cipher cipher = Cipher.getInstance("AES/CBC/PKCS5Padding");
	cipher.init(Cipher.DECRYPT_MODE, secretKey, new IvParameterSpec(iv));
	String plaintext = new String(cipher.doFinal(ciphertext), "UTF-8");
	System.out.println(plaintext);
	return plaintext;
    }

    public byte [] encrypt(String plaintext) throws NoSuchAlgorithmException, InvalidKeySpecException, NoSuchAlgorithmException, InvalidKeyException, IllegalBlockSizeException, NoSuchPaddingException, InvalidParameterSpecException, BadPaddingException {
	System.out.printf("encrypt('%s')\n",plaintext);
	
	SecretKey secretKey = generateKey(passphrase, this.salt, ITERATIONS, KEY_LENGTH);
	Cipher cipher = Cipher.getInstance("AES/CBC/PKCS5Padding");
	System.out.println("  getting cipher instance");
	cipher.init(Cipher.ENCRYPT_MODE, secretKey);
	System.out.println("  initialized encrypt mode");
	AlgorithmParameters params = cipher.getParameters();
	this.iv = params.getParameterSpec(IvParameterSpec.class).getIV();
	System.out.printf("  generated IV (length=%d)\n", this.iv.length);
	displayArray(this.iv);
	byte[] ciphertext = cipher.doFinal(plaintext.getBytes());
	System.out.printf("  ciphertext (length=%d)\n", ciphertext.length);
	displayArray(ciphertext);
	return ciphertext;
    }

    private static SecretKey generateKey(String passphrase, byte [] salt, int iterations, int outputKeyLength) throws NoSuchAlgorithmException, InvalidKeySpecException {
	System.out.printf("generateKey('%s', salt, %d, %d)\n", passphrase, iterations, outputKeyLength);

	KeySpec kspec = new PBEKeySpec(passphrase.toCharArray(), salt, iterations, outputKeyLength /* in bits */);
	System.out.println("  created key spec");
        SecretKeyFactory skf = SecretKeyFactory.getInstance("PBKDF2WithHmacSHA1");
	System.out.println("  got instance of secretkeyfactory");
        byte[] hash = skf.generateSecret(kspec).getEncoded(); // pbkdf hash result
	displayArray(hash);

	SecretKey secretKey = new SecretKeySpec(hash, "AES");
	System.out.println("  generated secret key for AES");
	displayArray(secretKey.getEncoded());
	return secretKey;
    }

    public static void main(String args[]) {
	try {
	    Mycrypto c = new Mycrypto();
	    byte [] ciphertext = c.encrypt(c.plaintext);
	    c.decrypt(ciphertext);
	}
	catch (Exception exp) {
	    System.out.printf("Message: %s", exp.getMessage());
	    System.out.println("Trace:");
	    exp.printStackTrace();
	}
    }
}
