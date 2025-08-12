import java.io.*;
import java.security.InvalidKeyException;
//import EncryptStrings;

public class FlagAlgo {
    public static final String KEY = "BABABEEF";
    public static final String FLAG = "ph0wn{ThisWasAReversibleDex}";

    public static void checkKey(String key) throws InvalidKeyException {
	if (key.length() != 8) throw new InvalidKeyException();

	for (int i=0; i< key.length(); i++) {
	    if (key.charAt(i) < 'A' || key.charAt(i) > 'F')
		throw new InvalidKeyException();
	}
    }

    public static String bruteforce(String ciphertext) {
	byte [] key = new byte [8];
	for (key[0] = 'A'; key[0] <= 'F'; key[0]++) {
	    for (key[1] = 'A'; key[1] <= 'F'; key[1]++) {
		for (key[2] = 'A'; key[2] <= 'F'; key[2]++) {
		    for (key[3] = 'A'; key[3] <= 'F'; key[3]++) {
			for (key[4] = 'A'; key[4] <= 'F'; key[4]++) {
			    for (key[5] = 'A'; key[5] <= 'F'; key[5]++) {
				for (key[6] = 'A'; key[6] <= 'F'; key[6]++) {
				    for (key[7] = 'A'; key[7] <= 'F'; key[7]++) {
					try {
					    EncryptStrings algo = new EncryptStrings(key);
					    String plain = algo.decrypt(ciphertext);
					    System.out.println("Key="+new String(key)+" plain="+algo.decrypt(ciphertext));
					    if (plain.substring(0, 5).equals("ph0wn")) {
						System.out.println("FOUND");
						return plain;
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
	return null;
    }

    public static void main(String [] args) {
	try {
	    checkKey(KEY);
	    EncryptStrings algo = new EncryptStrings(KEY.getBytes());
	    String encrypted_flag = algo.encrypt(FLAG);
	    System.out.println("Encrypted flag: "+encrypted_flag);
	    System.out.println("Decrypted flag: "+algo.decrypt(encrypted_flag));
	    System.out.println("-----------------");
	    //bruteforce(encrypted_flag);
	}
	catch(Exception exp) {
	    System.out.println("An exception occurred");
	}
    }
}

    
