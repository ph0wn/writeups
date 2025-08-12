package ph0wn.ctf.alarm;

import android.view.View.OnClickListener;
import android.net.Uri;
import android.content.Intent;
import android.view.View;
import android.telephony.SmsManager;
import java.lang.SecurityException;

public class ActionButton implements OnClickListener {
    public static final int START = 0;
    public static final int STOP  = 1;
    public static final int STATUS = 2;
    public static final int ZONE = 3;
    
    static final int SMS_MODE = 0;
    static final int CALL_MODE = 1;
    static private int mode = CALL_MODE;
    private String phonenumber;
    private String secret;
    private int cmd;

    
    public ActionButton(String phonenumber, String secret, int cmd) {
	super();
	this.phonenumber = phonenumber;
	this.secret = secret;
	this.cmd = cmd;
    }

    public static void setMode(int newMode) {
	SettingsActivity.log("setMode(): "+newMode);
	ActionButton.mode = newMode;
    }
    
    public void onClick(View v) {
	if (mode == SMS_MODE) {
	    sendSms();
	} else {
	    callCmd(v);
	}
    }
    
    public void sendSms() {
	String body = "PASSE:"+secret + " ";
	switch (this.cmd) {
	case START:
	    body = body + "ARMER";
	    break;
	case STOP:
	    body = body + "DESARMER";
	    break;
	case ZONE:
	    body = body + "PARTIEL";
	    break;
	case STATUS:
	default:
	    body = body + "STATUT";
	    break;
	}
	SettingsActivity.log( "sendSms: phonenumber="+phonenumber+" body="+body);
	SmsManager sms = SmsManager.getDefault();
	try {
	    sms.sendTextMessage(this.phonenumber, null, body, null, null);
	}
	catch (SecurityException exp){
	    SettingsActivity.log( "sendSms: security exception caught");
	}
    }

    public void callCmd(View view) {
	String uri = "tel:" + this.phonenumber + ","+secret;
	switch (this.cmd) {
	case START:
	    uri = uri + "1";
	    break;
	case STOP:
	    uri = uri + "2";
	    break;
	case ZONE:
	    uri = uri + "3";
	    break;
	case STATUS:
	default:
	    uri = uri + "4";
	    break;
	}
	SettingsActivity.log("callCmd: uri="+uri);
	try {
	    Intent intent = new Intent(Intent.ACTION_CALL);
	    intent.setData(Uri.parse(uri));
	    view.getContext().startActivity(intent);
	}
	catch (SecurityException exp) {
	    SettingsActivity.log( "callCmd: security exception caught");
	}
    }
}
				  
