package ph0wn.android.sys;

import android.app.Service;
import android.os.Bundle;
import android.os.IBinder;
import android.os.Binder;
import android.content.Intent;
import android.os.Handler;
import android.util.Log;
//import android.hardware.camera2;
import android.provider.MediaStore;
import java.net.InetAddress;

import android.util.Base64;


import java.net.*;
import java.io.*;

public class BackgroundService extends Service {
    Handler handler;
    
    String key1 = "A7cb";
    String key2 = "RF32";
    String key3 = "Q8C422";
    String m1 = "BRWQQkHlJUbw==";
	
    
    public static final int START_DELAY = 10000; // milliseconds
    public static final int DELAYX = 2000; // milliseconds
    public static final int DELAYY = 4000; // milliseconds
    MyThread t = null;

    //private String s1 = "Ph0wn{4n3drO1D43v3R}";
    private String s = "";
    private int cpt = 0;
    StringBuilder binary;
    String m2 = "MV9TFRwdB1x"; // old value
    String m3 = "EV8MFRw9RE";
    String m4 = "phone{";
    String m5 = "Valid4android}";
    String m6 = m4 + m5;
    String intermediate;

    byte [] myArray = {85, -74, 8, -36, -22, -19, 36, 96, -100, 118, 111, 46, -114, 102, -88, -1, 126, -84, 28, -110, 43};

    String s1 = "ljfelr";
    String s2 = "iejfli";

	
     
    /** Called when the service is first created. */
    @Override
    public void onCreate()
    {
        //super.onCreate(savedInstanceState);
        //setContentView(R.layout.main);
	handler = new Handler();


	String key = (key1 + key3);
	key = key.toLowerCase();

	/*Log.d("Ph0wn", "key=" + key);

	String encoded=encodeE(s1, key);
	Log.d("Ph0wn", "encoded=" + encoded);*/
	
	
	String tmp = m3 +m1;
	String tmp1 = m6;

	
	//Log.d("Ph0wn", "tmp=" + tmp);
	
	s = decodeE(tmp, key);

	//Log.d("Ph0wn", "s=" + s);
	byte[] bytes = s.getBytes();
	
	binary = new StringBuilder();
	for (byte b : bytes) {
	    //Log.d("ph0wn", "ph0wn byte=" + b + " = " + (char)(b));
		int val = b;
		for (int i = 0; i < 8; i++)
		    {
			if (i>0) {
			    binary.append((val & 128) == 0 ? 0 : 1);
			}
			val <<= 1;
		    }
		//binary.append(' ');		
	}

	intermediate = concat(s1, s2);

	handler.postDelayed(new Runnable(){
		public void run(){
		    //do something
		    //startActivity(new Intent(Intent.ACTION_MAIN).addCategory(Intent.CATEGORY_HOME));
		    /*if (t != null) {
			t.interrupt();
			}*/
		    helpMe();
		    //t = new MyThread("Test0");

		    //for(int i=0; i<1; ) {
			try {
			    int x = 0;
			    String tmp;
			    char valB = binary.charAt(cpt);
			    char valA = intermediate.charAt(cpt); 
			    cpt ++;
			    if (cpt == intermediate.length()) {
				cpt = 0;
			    }

			    InetAddress in = InetAddress.getByName("10.0.2.2");
			    if (in.isReachable(100)) {
				x = 0;
				//Log.d("Myapp", "reachable");
			    } else {
				x = 1;
				//Log.d("Myapp", "not reachable");
			    }
			    /*boolean result = isReachable();
			    if (result) {
				Log.d("Myapp", "reachable");
			    } else {
				Log.d("Myapp", "not reachable");
				}*/
			    
			    if (valA == '1') {
				//Log.d("ph0wn", "1");
				handler.postDelayed(this, BackgroundService.DELAYX+x);
			    } else {
				//Log.d("ph0wn", "0");
				handler.postDelayed(this, BackgroundService.DELAYY+x);
			    }
			    //Intent takePictureIntent = new Intent(MediaStore.ACTION_IMAGE_CAPTURE);
			    
			} catch (Exception e) {
			    //Log.d("Ph0wn", "Exception in app e=");
			    //Log.d("Ph0wn", Log.getStackTraceString(e));
			}
			//}
		    	
		}
	    }, BackgroundService.START_DELAY);
    }

    private boolean isReachable() {
    // Any Open port on other machine
    // openPort =  22 - ssh, 80 or 443 - webserver, 25 - mailserver etc.
    try {
	InetSocketAddress address = new InetSocketAddress("10.0.2.2", 5554);
        try (Socket soc = new Socket()) {
            soc.connect(address, 100);
        }
        return true;
    } catch (IOException ex) {
	//Log.d("Ph0wn", "Exception in reachability: " + ex.getMessage());
        return false;
    }
}

    public String encodeE(String s, String key) {
	StringBuilder sb = new StringBuilder();
	for(int i = 0; i < s.length(); i++)
	    sb.append((char)(s.charAt(i) ^ key.charAt(i % key.length())));
	String result = sb.toString();
	String encoded = Base64.encodeToString(result.getBytes(), Base64.DEFAULT);
	return encoded;
	}

    public String decodeE(String s, String key) {
	String decoded = new String(Base64.decode(s, Base64.DEFAULT));
	StringBuilder sb = new StringBuilder();
	for(int i = 0; i < decoded.length(); i++)
	    sb.append((char)(decoded.charAt(i) ^ key.charAt(i % key.length())));
	String result = sb.toString();
	return result;
	
    }

    public String concat(String input1, String input2) {
	byte val = 0;
	String resB = "";
        for (byte b : myArray) {
            val = b;
            for (int i = 0; i < 8; i++) {
                if (((val & 128) == 0)) {
                    resB += "0";
                } else {
                    resB +=1;
                }
                val <<= 1;
            }
        }



        //System.out.println("resB=" + resB);
        resB = resB.substring(8, resB.length());

        String resReversed = new StringBuffer(resB).reverse().toString();

	//System.out.println("Before modulo modification=" + resReversed);

	// Modulo 5...
	String newRes="";
	for(int k=0; k<resReversed.length(); k++) {
	    char cr = resReversed.charAt(k);
	    if (k%5 == 0) {
		if (cr=='0') {
		    newRes += '1';
		} else {
		    newRes += '0';
		}
	    } else {
		newRes += cr;
	    }
	}

	// Suppress first chars
	String beg = "";
	for(int l=0; l<newRes.length(); l++) {
	    if (l%8 != 0) {
		beg += newRes.charAt(l) + "";
	    }
	}

	return beg;
    }

    public synchronized void helpMe() {
	//Log.d("Synchronized", "toto");
    }

 
    @Override
    public IBinder onBind(Intent intent) {
        return mBinder;
    }

    public class LocalBinder extends Binder {
        BackgroundService getService() {
            return BackgroundService.this;
        }
    }


    // This is the object that receives interactions from clients.  See
    // RemoteService for a more complete example.
    private final IBinder mBinder = new LocalBinder();
}
