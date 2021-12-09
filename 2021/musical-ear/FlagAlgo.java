import java.io.*;
import java.io.UnsupportedEncodingException;
import java.security.InvalidKeyException;
import java.security.NoSuchAlgorithmException;
import java.util.Base64;
import javax.crypto.BadPaddingException;
import javax.crypto.Cipher;
import javax.crypto.IllegalBlockSizeException;
import javax.crypto.NoSuchPaddingException;
import javax.crypto.spec.SecretKeySpec;

/**
 * Quick bruteforcing for challenge musical-ear
 * @cryptax
 * Dec 9, 2021
 */

public class FlagAlgo {
    private SecretKeySpec sks;
    private Cipher cipher;

    public FlagAlgo(byte [] key)throws NoSuchPaddingException, NoSuchAlgorithmException {
        this.sks = new SecretKeySpec(key, "Blowfish");
        this.cipher = Cipher.getInstance("Blowfish");
    }

    public String decrypt(String input) throws InvalidKeyException, BadPaddingException, IllegalBlockSizeException, UnsupportedEncodingException {
        byte [] decoded = Base64.getDecoder().decode(input);
        this.cipher.init(Cipher.DECRYPT_MODE, this.sks);
        byte [] output = this.cipher.doFinal(decoded);
        return new String(output);
    }


    public static void main(String [] args) {
	String secsea = "BahoP2kAnYuRf5LqEW5umIRwZZD7iKrcOzdKXglXMKg=";

	// bruteforce
	byte [] key = new byte [8];
	for (key[0] = 'A'; key[0] <= 'G'; key[0]++) {
	    for (key[1] = 'A'; key[1] <= 'G'; key[1]++) {
		for (key[2] = 'A'; key[2] <= 'G'; key[2]++) {
		    for (key[3] = 'A'; key[3] <= 'G'; key[3]++) {
			for (key[4] = 'A'; key[4] <= 'G'; key[4]++) {
			    for (key[5] = 'A'; key[5] <= 'G'; key[5]++) {
				for (key[6] = 'A'; key[6] <= 'G'; key[6]++) {
				    for (key[7] = 'A'; key[7] <= 'G'; key[7]++) {
					try {
					    FlagAlgo algo = new FlagAlgo(key);
					    String plain = algo.decrypt(secsea);
					    System.out.println("Key="+new String(key)+" plain="+plain);
					    if (plain.substring(0, 5).equals("ph0wn")) {
						System.out.println("FOUND");
						System.exit(0);
					    }
					}
					catch(Exception exp) {
					    System.out.println("ERR for key="+new String(key)+" Excp: "+exp.toString());
					}
				    }
				}
			    }
			}
		    }
		}
	    }
	}
    }
}
