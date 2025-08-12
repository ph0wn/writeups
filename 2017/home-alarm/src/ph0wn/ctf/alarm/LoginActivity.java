package ph0wn.ctf.alarm;

import android.app.Activity;
import android.os.Bundle;
import android.widget.Button;
import android.widget.EditText;
import android.view.View;
import android.content.Intent;

public class LoginActivity extends Activity
{
    EditText passphraseET;
    Button loginBtn;
    Button settingsBtn;
    
    /** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState)
    {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.main);
	SettingsActivity.log( "LoginActivity: onCreate()");
	
	passphraseET = (EditText) findViewById(R.id.passphraseET);
	loginBtn = (Button) findViewById(R.id.loginBtn);
	settingsBtn = (Button) findViewById(R.id.settingsBtn);

	if (!Settings.isPresent(this)) {
	    // disable login button
	    loginBtn.setEnabled(false);
	    passphraseET.setEnabled(false);
	}

	settingsBtn.setOnClickListener(new View.OnClickListener()         {
            public void onClick(View v)             {
		// open settings
		SettingsActivity.log( "Settings button clicked");
		Intent intent = new Intent(LoginActivity.this, SettingsActivity.class);
		startActivity(intent);
	    }
	    });

	loginBtn.setOnClickListener(new View.OnClickListener()         {
            public void onClick(View v)             {
		SettingsActivity.log("Login button clicked");
		try {
		    Settings s = new Settings(passphraseET.getText().toString(), LoginActivity.this);
		    Intent intent = new Intent(LoginActivity.this, ControlActivity.class);
		    intent.putExtra("phonenumber", s.phonenumber);
		    intent.putExtra("secret", s.secret);
		    startActivity(intent);
		}
		catch(Exception exp) {
		    loginBtn.setEnabled(false);
		    SettingsActivity.log(exp.toString());
		}
	    }
	    });

    }

}
