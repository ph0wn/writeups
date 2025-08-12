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
import java.io.FileInputStream;
import java.io.InputStream;
import java.io.File;
import java.io.IOException;
import java.io.FileNotFoundException;

public class DecryptSettings {
    static final String PASSPHRASE = "NoBurglarsAtHome";
    static final int KEY_LENGTH = 128;
    static final int SALT_LENGTH = 64;
    private int iterations = 1000;
    private byte [] salt = null;
    private byte [] iv = null;

    public static void displayArray(byte [] array) {
	int i;
	for (i=0; i< array.length; i++) {
	    System.out.printf("%02x ", array[i]);
	}
	System.out.println("");
    }

    public DecryptSettings() { }

    private void generateSalt() throws NoSuchAlgorithmException {
    	SecureRandom sr = SecureRandom.getInstance("SHA1PRNG");
	this.salt = new byte[SALT_LENGTH];
	sr.nextBytes(this.salt);
	System.out.printf("  Salt array (length=%d)\n", this.salt.length);
	displayArray(this.salt);
    }

    public String decrypt(String passphrase, byte [] ciphertext) throws NoSuchAlgorithmException, InvalidKeyException, IllegalBlockSizeException, NoSuchPaddingException, InvalidKeySpecException, InvalidAlgorithmParameterException, BadPaddingException, UnsupportedEncodingException {
	assert this.iv != null;
	assert ciphertext != null;
	
	SecretKey secretKey = generateKey(passphrase, this.salt, this.iterations, KEY_LENGTH);
	Cipher cipher = Cipher.getInstance("AES/CBC/PKCS5Padding");
	cipher.init(Cipher.DECRYPT_MODE, secretKey, new IvParameterSpec(this.iv));
	String plaintext = new String(cipher.doFinal(ciphertext), "UTF-8");
	System.out.println(plaintext);
	return plaintext;
    }

    public byte [] encrypt(String passphrase, String plaintext) throws NoSuchAlgorithmException, InvalidKeySpecException, NoSuchAlgorithmException, InvalidKeyException, IllegalBlockSizeException, NoSuchPaddingException, InvalidParameterSpecException, BadPaddingException {
	System.out.printf("encrypt('%s')\n",plaintext);

	if (this.salt == null) {
	    generateSalt();
	}

	assert plaintext != null;
	
	SecretKey secretKey = generateKey(passphrase, this.salt, this.iterations, KEY_LENGTH);
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
	assert passphrase != null;
	assert salt != null;
	assert iterations > 0;
	assert outputKeyLength > 0;
	
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

    public void readSettings(String passphrase, File file)  throws FileNotFoundException, IOException, ArrayIndexOutOfBoundsException, NoSuchAlgorithmException, InvalidKeyException, IllegalBlockSizeException, NoSuchPaddingException, InvalidKeySpecException, InvalidAlgorithmParameterException, BadPaddingException {
	System.out.printf("readSettings('%s', file)\n", passphrase);
	FileInputStream fis = new FileInputStream(file);

	// Reading salt
	byte [] b = new byte[1];
	fis.read(b); // tag
	fis.read(b); // length
	int len = b[0];
	byte [] salt = new byte[len];
	fis.read(salt);
	displayArray(salt);

	// Reading IV
	fis.read(b); // tag
	fis.read(b); // length
	len = b[0];
	byte [] iv = new byte[len];
	fis.read(iv);
	displayArray(iv);

	// Reading iterations
	fis.read(b); // tag
	fis.read(b); // iterations / 1000
	int iterations = b[0] * 1000;
	System.out.printf("  Iterations=%d\n",iterations);

	// Reading ciphertext
	fis.read(b);
	fis.read(b); // ciphertext length
	len = b[0];
	byte [] ciphertext = new byte[len];
	fis.read(ciphertext);
	displayArray(ciphertext);
	
	SecretKey secretKey = generateKey(passphrase, salt, iterations, KEY_LENGTH);
	Cipher cipher = Cipher.getInstance("AES/CBC/PKCS5Padding");
	cipher.init(Cipher.DECRYPT_MODE, secretKey, new IvParameterSpec(iv));
	String plaintext = new String(cipher.doFinal(ciphertext), "UTF-8");
	System.out.printf("  Plaintext=%s",plaintext);
	fis.close();
    }
	

    public static void main(String args[]) {
	try {
	    DecryptSettings c = new DecryptSettings();
	    File f = new File("settings.dat");
	    c.readSettings(DecryptSettings.PASSPHRASE, f);
	}
	catch (Exception exp) {
	    System.out.printf("Message: %s", exp.getMessage());
	    System.out.println("Trace:");
	    exp.printStackTrace();
	}
    }
}
