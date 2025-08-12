package ph0wn.ctf.alarm;

import android.app.Activity;
import android.os.Bundle;
import android.widget.Button;
import android.widget.EditText;
import android.util.Log;
import android.view.View;
import android.content.Intent;

public class SettingsActivity extends Activity
{
    public static final boolean DEBUG = false;
    EditText phoneET, secretET, passphraseET;
    Button doneBtn;

    public static void log(String msg) {
	if (DEBUG) {
	    Log.i("Ph0wn", msg);
	}
    }

    public static void logByteArray(byte [] array) {
	if (DEBUG) {
	    for (int index = 0; index < array.length; index++){
		Log.i("Ph0wn", String.format("    0x%02x", array[index]));
	    }
	}
    }

    /** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState)
    {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.settings);

	phoneET = (EditText) findViewById(R.id.phoneET);
	secretET = (EditText) findViewById(R.id.secretET);
	passphraseET = (EditText) findViewById(R.id.passphraseET);
	doneBtn = (Button) findViewById(R.id.doneBtn);

	doneBtn.setOnClickListener(new View.OnClickListener()         {
            public void onClick(View v)             {
		SettingsActivity.log( "button clicked");

		String phonenumber = phoneET.getText().toString();
		String secret = secretET.getText().toString();
		String passphrase = passphraseET.getText().toString();
		Settings s = new Settings(phonenumber, secret, SettingsActivity.this);
		SettingsActivity.log( "SettingsActivity: phonenumber="+phonenumber);
		SettingsActivity.log( "SettingsActivity: secret="+secret);
		SettingsActivity.log( "SettingsActivity: passphrase="+passphrase);
		try {
		    s.saveSettings(passphrase);
		}
		catch(Exception exp) {
		    SettingsActivity.log("failed to save settings");
		}
		
		Intent intent = new Intent(SettingsActivity.this, ControlActivity.class);
		intent.putExtra("phonenumber", phonenumber);
		intent.putExtra("secret", secret);
		startActivity(intent);
	    }
	    });

    }

}
