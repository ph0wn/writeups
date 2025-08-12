package ph0wn.reconjet;

import android.app.Activity;
import android.os.Bundle;
import android.widget.TextView;
import android.widget.ImageView;
import android.graphics.drawable.Drawable;
import android.util.Log;
import java.io.*;

public class MainActivity extends Activity
{
    public TextView txtView = null;
    public ImageView mImage = null;

    /** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState)
    {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.main);

	txtView = (TextView) findViewById(R.id.txtView);
	mImage = (ImageView)findViewById(R.id.image);
	
	try {
	    InputStream ims = getAssets().open("qrcode.png");
	    Drawable d = Drawable.createFromStream(ims, null);
	    mImage.setImageDrawable(d);
	    Log.i("Ph0wn", "Image displayed OK");
	}
	catch(IOException exp) {
	    Log.e("Ph0wn", "Image display exception caught: "+exp.toString());
	    exp.printStackTrace();
	    txtView.setText("An error occurred :(");
	}
	    
    }
}
