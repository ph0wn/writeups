package ph0wn.payload;

import android.util.Base64;

import java.io.UnsupportedEncodingException;
import java.security.InvalidKeyException;
import java.security.NoSuchAlgorithmException;

import javax.crypto.BadPaddingException;
import javax.crypto.Cipher;
import javax.crypto.IllegalBlockSizeException;
import javax.crypto.NoSuchPaddingException;
import javax.crypto.spec.SecretKeySpec;

public class EncryptStrings {
    private static final byte [] KEYDATA = "ph0wn_ctf_is_cool".getBytes();
    private SecretKeySpec sks;
    private Cipher cipher;

    public EncryptStrings() throws NoSuchPaddingException, NoSuchAlgorithmException {
        this.sks = new SecretKeySpec(KEYDATA, "Blowfish");
        this.cipher = Cipher.getInstance("Blowfish");
    }

    public EncryptStrings(String key) throws NoSuchPaddingException, NoSuchAlgorithmException, InvalidKeyException {
        if (key.length() != 8) throw new InvalidKeyException();

        for (int i=0; i< key.length(); i++) {
            if (key.charAt(i) < 'A' || key.charAt(i) > 'F')
                throw new InvalidKeyException();
        }

        this.sks = new SecretKeySpec(key.getBytes(), "Blowfish");
        this.cipher = Cipher.getInstance("Blowfish");
    }

    public String encrypt(String input) throws InvalidKeyException, BadPaddingException, IllegalBlockSizeException {
        this.cipher.init(Cipher.ENCRYPT_MODE, this.sks);
        byte [] output = this.cipher.doFinal(input.getBytes());
        return Base64.encodeToString(output, Base64.DEFAULT);
    }

    public String decrypt(String input) throws InvalidKeyException, BadPaddingException, IllegalBlockSizeException, UnsupportedEncodingException {
        byte [] decoded = Base64.decode(input, Base64.DEFAULT);
        this.cipher.init(Cipher.DECRYPT_MODE, this.sks);
        byte [] output = this.cipher.doFinal(decoded);
        return new String(output);
    }

}
