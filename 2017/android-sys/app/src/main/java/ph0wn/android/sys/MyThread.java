package ph0wn.android.sys;

import android.app.Activity;
import android.os.Bundle;
import android.content.Intent;
import android.os.Handler;
import android.util.Log;

class MyThread extends Thread {
    private String name;
    
    public MyThread(String _name) {
	name = _name;
    }

    public void run(){
	//try {
	    while(!Thread.currentThread().isInterrupted()) {
		// ...
	    }
	    //} catch (InterruptedException consumed) {
	    Log.d("Thread", "interrupted" + name);
	    /* Allow thread to exit */
	    //}
    }
}
