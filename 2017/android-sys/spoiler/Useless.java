import java.util.Base64;
import java.io.UnsupportedEncodingException;

public class Useless {
    public static final String B = "A7cb";
    public static final String I = "EV8MFRw9RE";
    public static final String E = "BRWQQkHlJUbw==";
    public static final String D = "q8c422";
    
    public String base64xor(String base64str, String key) {
        int v0 = 0;
	byte [] v1 = Base64.getDecoder().decode(base64str);
	//        String v1 = new String(Base64.decode(base64str, 0));
	System.out.println(new String(v1));
        StringBuilder v2 = new StringBuilder();
        while(v0 < v1.length) {
            v2.append(((char)(v1[v0] ^ key.charAt(v0 % key.length()))));
            ++v0;
        }

        return v2.toString();
    }

    public static void main(String args[]) {
	System.out.println("Working on the solution");
	Useless soluce = new Useless();
	String b = Useless.I + Useless.E;
	String k = Useless.B + Useless.D;
	String ret = soluce.base64xor(b,k);
	System.out.println(ret);
    }
}
