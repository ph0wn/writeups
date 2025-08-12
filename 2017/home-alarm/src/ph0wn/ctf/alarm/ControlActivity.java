package ph0wn.ctf.alarm;

import android.app.Activity;
import android.os.Bundle;
import android.widget.Button;
import android.content.Intent;
import android.view.View;
import android.content.Intent;
import android.widget.CompoundButton.OnCheckedChangeListener;
import android.widget.CompoundButton;
import android.widget.Switch;

public class ControlActivity extends Activity
{
    String phonenumber = "";
    String secret = "";
    Button startBtn;
    Button stopBtn;
    Button statusBtn;
    Button zoneBtn;
    Button logoutBtn;
    Switch smsSwitch;
    
    /** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState)
    {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.control);

	startBtn = (Button) findViewById(R.id.startBtn);
	stopBtn = (Button) findViewById(R.id.stopBtn);
	statusBtn = (Button) findViewById(R.id.statusBtn);
	zoneBtn = (Button) findViewById(R.id.zoneBtn);
	logoutBtn = (Button) findViewById(R.id.logoutBtn);
	smsSwitch = (Switch) findViewById(R.id.smsSwitch);
	
	Intent intent = this.getIntent();
	phonenumber = intent.getStringExtra("phonenumber");	
	SettingsActivity.log( "ControlActivity: phonenumber="+phonenumber);
	secret = intent.getStringExtra("secret");
	SettingsActivity.log( "ControlActivity: secret="+secret);

	// default control mode is CALL
	smsSwitch.setChecked(false);
	ActionButton.setMode(ActionButton.CALL_MODE); 

	startBtn.setOnClickListener(new ActionButton(phonenumber, secret, ActionButton.START));
	stopBtn.setOnClickListener(new ActionButton(phonenumber, secret, ActionButton.STOP));
	statusBtn.setOnClickListener(new ActionButton(phonenumber, secret, ActionButton.STATUS));
	zoneBtn.setOnClickListener(new ActionButton(phonenumber, secret, ActionButton.ZONE));
	logoutBtn.setOnClickListener(new View.OnClickListener()         {
            public void onClick(View v)             {
		SettingsActivity.log("Logout button clicked");
		phonenumber = "";
		secret = "";
		Intent intent = new Intent(ControlActivity.this, LoginActivity.class);
		startActivity(intent);
	    }
	    });
	smsSwitch.setOnCheckedChangeListener(new OnCheckedChangeListener() {
		@Override
		public void onCheckedChanged(CompoundButton buttonView,
					     boolean isChecked) {
		    if(isChecked){
			SettingsActivity.log("Sms mode has been switched on");
			ActionButton.setMode(ActionButton.SMS_MODE);
		    }else{
			SettingsActivity.log("Sms mode has been switched off");
			ActionButton.setMode(ActionButton.CALL_MODE);
		    }
		}
	    });
    }

}
