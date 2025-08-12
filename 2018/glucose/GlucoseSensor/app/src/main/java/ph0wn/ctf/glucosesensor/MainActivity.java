package ph0wn.ctf.glucosesensor;

import android.app.Activity;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.os.Bundle;

import org.eclipse.paho.android.service.MqttAndroidClient;

import android.preference.PreferenceManager;
import android.support.v4.content.LocalBroadcastManager;
import android.support.v7.app.AppCompatActivity;
import android.support.v7.widget.Toolbar;
import android.util.Log;
import android.view.Menu;
import android.view.MenuItem;
import android.widget.TextView;

public class MainActivity extends AppCompatActivity {
    MqttAndroidClient mqttAndroidClient;
    static public final String TAG = "Glucose";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        Toolbar myToolbar = findViewById(R.id.my_toolbar);
        setSupportActionBar(myToolbar);
        PreferenceManager.setDefaultValues(this, R.xml.glucosesettings, false);

        final TextView alert = findViewById(R.id.alert);
        final TextView info = findViewById(R.id.info);
        final TextView level = findViewById(R.id.level);


        Intent intent = new Intent(MainActivity.this, GlucoseService.class);
        startService(intent);

        LocalBroadcastManager.getInstance(this).registerReceiver(
                new BroadcastReceiver() {
                    @Override
                    public void onReceive(Context context, Intent intent) {
                        String topic = intent.getStringExtra(GlucoseService.EXTRA_MQTT_TOPIC);
                        String payload = intent.getStringExtra(GlucoseService.EXTRA_MQTT_PAYLOAD);

                        if (topic.equals("ph0wn18/glucose-level")) {
                            level.setText(payload+ " mg/dL");
                        }
                        if (topic.equals("ph0wn18/info")) {
                            info.setText(payload);
                        }
                        if (topic.equals("ph0wn18/alert")) {
                            alert.setText(payload);
                        }

                    }
                }, new IntentFilter(GlucoseService.ACTION_MQTT_BROADCAST)
        );
    }

    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.main, menu);
        return true;
    }

    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        switch (item.getItemId()) {
            case R.id.settings:
                Intent intent = new Intent(this, SettingsActivity.class);
                startActivity(intent);
                return true;
        }
        return super.onOptionsItemSelected(item);
    }

}
