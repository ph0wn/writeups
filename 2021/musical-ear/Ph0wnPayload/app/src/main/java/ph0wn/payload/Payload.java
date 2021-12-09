package ph0wn.payload;

import android.content.Context;
import android.util.Log;
import android.widget.Toast;

public class Payload  {
    private String hint1, hint2, congrats;
    private String flag = "BahoP2kAnYuRf5LqEW5umIRwZZD7iKrcOzdKXglXMKg="; // ph0wn{ThisWasAReversibleDex} - the key is BABABEEF

    public Payload() {
        String h1 = "3o+5cMuuSsxu7CCWGnXsNZvWlagjhBKp"; //"You are doing good lol";
        String h2 = "e4IPX80xn9lWezbzmmlWLFrQau0tshKHI3sm8mPQunE="; //Reverse the Dex, banana!";
        String c3 = "jx6Z9opTknoew+UnHPijoBgTjqDgdNuTTYzx3CJUPCX07UKOxU/S2g=="; //Congrats! You have a musical ear!
        try {
            EncryptStrings algo = new EncryptStrings();
            hint1 = algo.decrypt(h1);
            hint2 = algo.decrypt(h2);
            congrats = algo.decrypt(c3);
        }
        catch (Exception exp){
            Log.e("ph0wn", "Payload(): decryption exception: "+exp);
        }
    }

    public void hint1(Context ctx){
        Toast.makeText(ctx, hint1, Toast.LENGTH_SHORT).show();
    }

    public void hint2(Context ctx){
        Toast.makeText(ctx, hint2, Toast.LENGTH_SHORT).show();
    }

    public void congrats(Context ctx){
        Toast.makeText(ctx, congrats, Toast.LENGTH_SHORT).show();
    }

    public void displayFlag(Context ctx, String tune_sequence){
        try {
            EncryptStrings algo = new EncryptStrings(tune_sequence);
            String decrypted_flag = algo.decrypt(this.flag);
            Toast.makeText(ctx, decrypted_flag, Toast.LENGTH_SHORT).show();
            Log.d("ph0wn", decrypted_flag);
        } catch (Exception exp) {
            Log.w("ph0wn", "Unable to decrypt correctly");
            Toast.makeText(ctx, "Bad decryption key", Toast.LENGTH_SHORT).show();
        }
    }
}
