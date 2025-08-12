import java.io.UnsupportedEncodingException;
import java.security.InvalidKeyException;
import java.security.NoSuchAlgorithmException;
import java.util.Base64;
import javax.crypto.BadPaddingException;
import javax.crypto.Cipher;
import javax.crypto.IllegalBlockSizeException;
import javax.crypto.NoSuchPaddingException;
import javax.crypto.spec.SecretKeySpec;

public class EncryptStrings {
    private static final byte [] KEYDATA = "ph0wn_ctf_is_cool".getBytes();
    private SecretKeySpec sks;
    private Cipher cipher;

    public EncryptStrings(byte [] key)throws NoSuchPaddingException, NoSuchAlgorithmException {
        this.sks = new SecretKeySpec(key, "Blowfish");
        this.cipher = Cipher.getInstance("Blowfish");
    }

    public EncryptStrings() throws NoSuchPaddingException, NoSuchAlgorithmException {
	this(KEYDATA);
    }

	

    public String encrypt(String input) throws InvalidKeyException, BadPaddingException, IllegalBlockSizeException {
        this.cipher.init(Cipher.ENCRYPT_MODE, this.sks);
        byte [] output = this.cipher.doFinal(input.getBytes());
        return Base64.getEncoder().encodeToString(output);
    }

    public String decrypt(String input) throws InvalidKeyException, BadPaddingException, IllegalBlockSizeException, UnsupportedEncodingException {
        byte [] decoded = Base64.getDecoder().decode(input);
        this.cipher.init(Cipher.DECRYPT_MODE, this.sks);
        byte [] output = this.cipher.doFinal(decoded);
        return new String(output);
    }
    
    public static void main(String args[]) {
	try {
	String [] inputs = {"You are doing good lol",
			    "Reverse the Dex, banana!",
			    "Congrats! You have a musical ear!"
	};

	EncryptStrings es = new EncryptStrings();
	
	for (int i=0; i<inputs.length; i++) {
	    String encrypted = es.encrypt(inputs[i]);
	    System.out.print("i="+i+" encrypted="+encrypted);
	    String decrypted = es.decrypt(encrypted);
	    System.out.println(" decrypted="+decrypted);
	}
	}
	catch(Exception exp) {
	    System.out.println(exp.toString());
	}
    }

}
