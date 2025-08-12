package ph0wn.ctf.glucosesensor;


import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.os.IBinder;
import android.preference.Preference;
import android.preference.PreferenceManager;
import android.support.v4.content.LocalBroadcastManager;
import android.util.Log;
import android.app.Service;
import android.widget.TextView;
//import android.widget.TextView;

import org.eclipse.paho.android.service.MqttAndroidClient;
import org.eclipse.paho.client.mqttv3.IMqttActionListener;
import org.eclipse.paho.client.mqttv3.IMqttDeliveryToken;
import org.eclipse.paho.client.mqttv3.IMqttToken;
import org.eclipse.paho.client.mqttv3.MqttCallbackExtended;
import org.eclipse.paho.client.mqttv3.MqttConnectOptions;
import org.eclipse.paho.client.mqttv3.MqttException;
import org.eclipse.paho.client.mqttv3.MqttMessage;

public class GlucoseService extends Service implements SharedPreferences.OnSharedPreferenceChangeListener {

    private MqttAndroidClient mqttClient = null;

    public static final String ACTION_MQTT_BROADCAST = "GlucoseMqttBroadcast";
    public static final String EXTRA_MQTT_TOPIC = "extra_mqtt_topic";
    public static final String EXTRA_MQTT_PAYLOAD = "extra_mqtt_payload";
    public static final int MQTT_PORT = 1883;
    public static final int TEST = 0;

    @Override
    public void onCreate() {
        super.onCreate();
        Log.d(MainActivity.TAG, "GlucoseService.onCreate()");

        this.createClient();
        //sendBroadcastMessage("ph0wn18/alert", "TEST");
    }

    private void createClient() {
        Log.d(MainActivity.TAG, "GlucoseService.createClient()");

        // Read current server URI
        SharedPreferences sharedPref = PreferenceManager.getDefaultSharedPreferences(this);
        String serverUri = "tcp://"+sharedPref.getString( SettingsActivity.SERVER_URI, "")+":"+MQTT_PORT;
        sharedPref.registerOnSharedPreferenceChangeListener(this);

        Log.d(MainActivity.TAG, "GlucoseService.createClient(): Server URI: "+serverUri);
        String clientId = getString(R.string.client_id);
        String username = getString(R.string.username);
        String password = getString(R.string.password);

        if (TEST == 1) {
            String [] test = getString(R.string.test_settings).replaceAll("@", "").replaceAll("#", "").replaceAll("!", "").split(":");
            username = Allatori.b(test[0]);
            password = Allatori.b(test[1]);
            //Log.d(MainActivity.TAG, username);
            //Log.d(MainActivity.TAG, password);
        }

        mqttClient = new MqttAndroidClient(getApplicationContext(), serverUri, clientId);
        this.connect(username, password);
        this.setClientCallback();
    }

    @Override
    public IBinder onBind(Intent intent) {
        return null;
    }

    private void setClientCallback() {
        Log.d(MainActivity.TAG, "GlucoseService.setClientCallback()");
        mqttClient.setCallback(new MqttCallbackExtended() {
            @Override
            public void connectComplete(boolean reconnect, String serverURI) {
                Log.d(MainActivity.TAG, "GlucoseService.setClientCallback.connectComplete(): connection completed - now subscribing");
                sendBroadcastMessage("ph0wn18/info", "Connection success");
            }

            @Override
            public void connectionLost(Throwable cause) {
                Log.d(MainActivity.TAG, "GlucoseService.setClientCallback.connectionLost(): connection lost");
                sendBroadcastMessage("ph0wn18/info", "Connection lost");
            }

            @Override
            public void messageArrived(String topic, MqttMessage message) {
                String payload = new String(message.getPayload());
                sendBroadcastMessage(topic, payload);
                Log.d(MainActivity.TAG, "Topic: "+topic+" payload: "+payload);
            }

            @Override
            public void deliveryComplete(IMqttDeliveryToken token) {

            }
        });
    }

    private void connect(String username, String password) {
        Log.d(MainActivity.TAG, "GlucoseService.connect(): user="+username+" password="+password);
        MqttConnectOptions mqttConnectOptions = new MqttConnectOptions();
        mqttConnectOptions.setAutomaticReconnect(true);
        mqttConnectOptions.setCleanSession(true);

        mqttConnectOptions.setUserName(username);
        mqttConnectOptions.setPassword(password.toCharArray());

        // connect
        try {
            IMqttToken token = this.mqttClient.connect(mqttConnectOptions);
            token.setActionCallback(new IMqttActionListener() {
                @Override
                public void onSuccess(IMqttToken asyncActionToken) {
                    Log.d(MainActivity.TAG, "GlucoseService.connect(): Success");
                    sendBroadcastMessage("ph0wn18/info", "Connected to ph0wn MQTT server");
                    subscribe("ph0wn18/glucose-level", 0);
                    subscribe("ph0wn18/info", 0);
                    subscribe("ph0wn18/alert", 0);
                }

                @Override
                public void onFailure(IMqttToken asyncActionToken, Throwable exception) {
                    Log.d(MainActivity.TAG, "connect(): Failure " + exception.toString());
                    sendBroadcastMessage("ph0wn18/info", "Failed to connect to MQTT server - check network");
                }
            });
        } catch (MqttException e) {
            e.printStackTrace();
            Log.e(MainActivity.TAG, "MQTT exception occurred: "+e.toString());
            sendBroadcastMessage("ph0wn18/info", "Error: "+e.toString());
        }
    }

    public void subscribe(final String topic, int qos) {
        Log.d(MainActivity.TAG, "GlucoseService.subscribe(): topic="+topic+" QoS="+qos);
        try {
            Log.d(MainActivity.TAG, mqttClient.toString());
            IMqttToken token = mqttClient.subscribe(topic, qos);
            token.setActionCallback(new IMqttActionListener() {

                @Override
                public void onSuccess(IMqttToken iMqttToken) {
                    Log.d(MainActivity.TAG, "GlucoseService.subscribe(): success for "+topic);
                }

                @Override
                public void onFailure(IMqttToken iMqttToken, Throwable throwable) {
                    Log.e(MainActivity.TAG, "GlucoseService.subscribe(): fail for " + topic);
                }
            });
        } catch(MqttException exp){
            exp.printStackTrace();
            Log.e(MainActivity.TAG, "MqttException in subscribe:"+exp.toString());
        }
    }

    private void sendBroadcastMessage(String topic, String payload) {
        Intent  intent = new Intent(ACTION_MQTT_BROADCAST);
        intent.putExtra(EXTRA_MQTT_TOPIC, topic);
        intent.putExtra(EXTRA_MQTT_PAYLOAD, payload);
        LocalBroadcastManager.getInstance(this).sendBroadcast(intent);
    }


    @Override
    public void onSharedPreferenceChanged(SharedPreferences sharedPref, String key) {
        Log.d(MainActivity.TAG, "GlucoseService.onSharedPreferenceChange()");
        if (key.equals(SettingsActivity.SERVER_URI)) {
            String serverUri = "tcp://"+sharedPref.getString( SettingsActivity.SERVER_URI, "")+":"+MQTT_PORT;
            Log.d(MainActivity.TAG, "New Server URI: "+serverUri);
            Log.d(MainActivity.TAG, "Disconnecting");
            sendBroadcastMessage("ph0wn18/glucose-level", "---");
            try {
                IMqttToken token = mqttClient.disconnect();
                token.setActionCallback(new IMqttActionListener() {
                    @Override
                    public void onSuccess(IMqttToken asyncActionToken) {
                        Log.d(MainActivity.TAG, "GlucoseService.disconnect.onSuccess - now reconnecting");
                        sendBroadcastMessage("ph0wn18/info", "disconnected");
                        createClient();
                    }

                    @Override
                    public void onFailure(IMqttToken asyncActionToken, Throwable exception) {
                        Log.w(MainActivity.TAG, "GlucoseService.disconnect.onFailure");
                    }
                });
            }
            catch(MqttException exp){
                exp.printStackTrace();
                Log.e(MainActivity.TAG, "MqttException in onSharedPreferenceChanged():"+exp.toString());
            }
        }
    }
}
