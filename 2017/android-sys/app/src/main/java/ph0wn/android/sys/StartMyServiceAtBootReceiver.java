package ph0wn.android.sys;

import android.app.Activity;
import android.os.Bundle;
import android.content.Intent;
import android.os.Handler;
import android.util.Log;
//import android.hardware.camera2;
import android.provider.MediaStore;
import java.net.InetAddress;

import android.util.Base64;

import android.content.BroadcastReceiver;
import android.content.Context;


public class StartMyServiceAtBootReceiver extends BroadcastReceiver {

    @Override
    public void onReceive(Context context, Intent intent) {
	Intent serviceIntent = new Intent(context, BackgroundService.class);
	Log.d("ph0wn", "Starting ph0wn app");
	context.startService(serviceIntent);
    }
}
