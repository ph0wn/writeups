package com.ph0wn.cispaman;

import android.app.Activity;
import android.os.Bundle;

public class StatueActivity extends Activity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        PrivacyView pview = new PrivacyView(this);
        setContentView(pview);
    }
}
