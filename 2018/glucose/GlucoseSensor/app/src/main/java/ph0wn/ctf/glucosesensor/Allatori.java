package ph0wn.ctf.glucosesensor;

import android.util.Base64;

public class Allatori {
    public static final byte KEY1 = (byte)0x34;
    public static final byte KEY2 = (byte)0x75;

    // obfuscation
    public static String a(String arg4) {
        int v0 = arg4.length();
        char[] v1 = new char[v0];
        --v0;
        while(v0 >= 0) {
            int v3 = v0 - 1;
            v1[v0] = ((char)(arg4.charAt(v0) ^ KEY1));
            if(v3 < 0) {
                break;
            }

            v0 = v3 - 1;
            v1[v3] = ((char)(arg4.charAt(v3) ^ KEY2));
        }

        return new String(v1);
    }

    // decode
    public static String b(String s){
        return Allatori.a(new String(Base64.decode(s, Base64.DEFAULT)));
    }

    // encode
    public static String c(String s){
        return new String(Base64.encode(Allatori.a(s).getBytes(), Base64.DEFAULT));
    }



}
