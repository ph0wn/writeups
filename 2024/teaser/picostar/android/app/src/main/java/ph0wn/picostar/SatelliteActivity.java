package ph0wn.picostar;

import androidx.appcompat.app.AppCompatActivity;
import android.os.Bundle;
import android.os.StrictMode;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;
import javax.crypto.Cipher;
import javax.crypto.spec.IvParameterSpec;
import javax.crypto.spec.SecretKeySpec;
import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.Base64;

import org.json.JSONException;
import org.json.JSONObject;

public class SatelliteActivity extends AppCompatActivity {
    private int currentSatelliteIndex = 0;
    // PicoSt*r++Caviar
    private final byte ENCRYPTION_KEY[] = { 0x50, 0x69, 0x63, 0x6f, 0x53, 0x74, 0x2a, 0x72, 0x2b, 0x2b, 0x43, 0x61, 0x76, 0x69, 0x61, 0x72 };
    // Sixteen byte IV!
    private final byte IV[] = { 0x53, 0x69, 0x78, 0x74, 0x65, 0x65, 0x6e, 0x20, 0x62, 0x79, 0x74, 0x65, 0x20, 0x49, 0x56, 0x21 };
    private final int NB_SATELLITES = 5;
    private TextView satelliteInfoTextView;


    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_satellite);

        this.satelliteInfoTextView  = findViewById(R.id.satelliteInfoTextView);
        Button displayNextButton = findViewById(R.id.displayNextButton);

        StrictMode.ThreadPolicy policy = new StrictMode.ThreadPolicy.Builder().permitAll().build();
        StrictMode.setThreadPolicy(policy);

        // Test: getFlag();

        displayNextButton.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                // Fetch satellite data from the server
                int satelliteId = currentSatelliteIndex + 1;
                String url = getResources().getString(R.string.server_ip) + "/satellite" + satelliteId;
                //Log.d("PicoStar", "Computed url: "+url);

                // Display the satellite data
                try {
                    JSONObject satelliteData = fetchSatelliteData(url);
                    satelliteInfoTextView.setText("Satellite #" + satelliteId + "\nLatitude: " + satelliteData.getDouble("latitude") +
                            "\nLongitude: " + satelliteData.getDouble("longitude") +
                            "\nAltitude: " + satelliteData.getDouble("altitude"));
                }
                catch (NullPointerException e) {
                    Log.e("PicoStar", "NullPointerException");
                }
                catch (JSONException e){
                    Log.w("PicoStar", "JSONException: "+e.getMessage());
                }
                // Increment the index to display the next satellite next time
                currentSatelliteIndex = (currentSatelliteIndex + 1) % NB_SATELLITES;
            }
        });
    }

    private JSONObject fetchSatelliteData(String url) {
        //TODO: remove logs
        try {
            HttpURLConnection connection = (HttpURLConnection) new URL(url).openConnection();
            BufferedReader reader = new BufferedReader(new InputStreamReader(connection.getInputStream()));
            StringBuilder response = new StringBuilder();
            String line;
            while ((line = reader.readLine()) != null) {
                response.append(line);
            }
            reader.close();
            connection.disconnect();

            // Decrypt the response using AES CBC
            //Log.d("PicoStar", "Encrypted response: "+response);
            String decryptedResponse = decryptAES_CBC(response.toString(), ENCRYPTION_KEY, IV);
            //Log.d("PicoStar", "Decrypted response: "+decryptedResponse);

            return new JSONObject(decryptedResponse);
        }
        catch (IllegalArgumentException b) {
            //Log.d("PicoStar", "Response is not a JSON: "+b);
            b.printStackTrace();
        }
        catch (Exception e) {
            //Log.d("PicoStar", "Exception Url="+url+" error: "+ e);
            e.printStackTrace();
        }
        return null;
    }

    private void getFlag() {
        //TODO remove all logs
        String entryPoint = "+60nXLtfb249m+F94blHhMZnUQs13OCFcLFIcSwXjQE=";
        try {
            String url = getResources().getString(R.string.server_ip) + "/" + decryptAES_CBC(entryPoint, ENCRYPTION_KEY, IV);
            //Log.d("PicoStar", "getFlag url=" + url);
            JSONObject response = fetchSatelliteData(url);
            //Log.d("PicoStar", "decrypted flag: " + response);
        } catch(Exception e){
            e.printStackTrace();
            //Log.w("PicoStar", "getFlag() exception: "+e.getMessage());
        }
    }

    private String decryptAES_CBC(String encryptedData, byte [] key, byte [] iv) throws Exception {
        Cipher cipher = Cipher.getInstance("AES/CBC/PKCS5Padding");
        SecretKeySpec secretKeySpec = new SecretKeySpec(key, "AES");
        IvParameterSpec ivParameterSpec = new IvParameterSpec(iv);
        cipher.init(Cipher.DECRYPT_MODE, secretKeySpec, ivParameterSpec);
        byte[] decryptedBytes = cipher.doFinal(Base64.getDecoder().decode(encryptedData));
        return new String(decryptedBytes);
    }
}


