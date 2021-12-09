package ph0wn.ctf.playfrequency;

import androidx.appcompat.app.AppCompatActivity;

import android.content.Context;
import android.os.Bundle;
import android.os.Handler;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;

import java.io.ByteArrayOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.lang.reflect.InvocationTargetException;
import java.lang.reflect.Method;
import java.util.Random;
import java.util.jar.JarFile;
import java.util.zip.ZipEntry;

import dalvik.system.DexClassLoader;


public class MainActivity extends AppCompatActivity implements View.OnClickListener {
    Handler handler = new Handler();
    private Button guessButton, buttonA, buttonAd, buttonB, buttonC, buttonCd, buttonD, buttonDd, buttonE, buttonF, buttonFd, buttonG, buttonGd;
    private TextView scoreTv, questionTv;
    private int currentIndex = 0;
    private int score = 0;
    private boolean answered = false;
    private Hints hints;
    private Context ctx;
    String pattern = "";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);
        this.ctx = getApplicationContext();
        hints = new Hints(ctx);
        /*try {
            hints.secret("displayFlag", "BABABEEF");
        } catch (NoSuchMethodException e) {
            e.printStackTrace();
        } catch (InstantiationException e) {
            e.printStackTrace();
        } catch (IllegalAccessException e) {
            e.printStackTrace();
        } catch (InvocationTargetException e) {
            e.printStackTrace();
        }*/

        initGui();
        enableAnswer(false);
    }

    private void initGui() {
        guessButton = findViewById(R.id.button);
        guessButton.setOnClickListener(this);

        buttonA = findViewById(R.id.buttonA);
        buttonAd = findViewById(R.id.buttonAd);
        buttonB = findViewById(R.id.buttonB);
        buttonC = findViewById(R.id.buttonC);
        buttonCd = findViewById(R.id.buttonCd);
        buttonD = findViewById(R.id.buttonD);
        buttonDd = findViewById(R.id.buttonDd);
        buttonE = findViewById(R.id.buttonE);
        buttonF = findViewById(R.id.buttonF);
        buttonFd = findViewById(R.id.buttonFd);
        buttonG = findViewById(R.id.buttonG);
        buttonGd = findViewById(R.id.buttonGd);
        scoreTv = (TextView)findViewById(R.id.textViewScore);
        questionTv = (TextView)findViewById(R.id.textViewQuestion);

        buttonA.setOnClickListener(this);
        buttonAd.setOnClickListener(this);
        buttonB.setOnClickListener(this);
        buttonC.setOnClickListener(this);
        buttonCd.setOnClickListener(this);
        buttonD.setOnClickListener(this);
        buttonDd.setOnClickListener(this);
        buttonE.setOnClickListener(this);
        buttonF.setOnClickListener(this);
        buttonFd.setOnClickListener(this);
        buttonG.setOnClickListener(this);
        buttonGd.setOnClickListener(this);
    }

    public void debug_log(String message){
        File file = new File(ctx.getFilesDir(), "cheatfile");
        if (file.exists()) {
            Log.d("ph0wn", message);
        }
    }

    public void guess() {
        final Thread thread = new Thread(new Runnable() {
            public void run() {
                handler.post(new Runnable() {

                    public void run() {
                        Random r = new Random();
                        currentIndex = r.nextInt(24);
                        int retries = 500;
                        boolean ok = false;
                        for (int i=0; i<retries; i++) {
                            // sometimes the audio track becomes unavailable. we just need to try again until it comes back
                            ok = PlayFrequency.playSound(PlayFrequency.notes[currentIndex]);
                            if (ok) {
                                answered = false;
                                enableAnswer(true);
                                pattern = pattern+ PlayFrequency.getShortName(PlayFrequency.notes[currentIndex]);
                                break;
                            }
                        }
                        if (!ok) {
                            debug_log( "guess(): unable to play the frequency for "+retries+" times");
                        }


                        debug_log("Playing " + PlayFrequency.getLongName(PlayFrequency.notes[currentIndex]));
                    }
                });
            }
        });
        thread.start();

    }

    private int check(int answer) {
        boolean good = false;

        // handle score
        if (currentIndex == answer ||
                (currentIndex >=12 && (currentIndex - 12) == answer)) {// we have a second octave...
            score++;
            good = true;
        }
        else
            score--;

        if (score < 0)
            score = 0;
        if (score > 20)
            score = 1;

        // call functions
        try {
            if (score == 20) {
                hints.showHint("congrats");
                String funcname = getString(R.string.funcname);
                hints.secret(funcname, pattern); // BABABEEF
            }
            else if (score == 10 && good)
                hints.showHint("hint2");
            else if (score == 2 && good)
                hints.showHint("hint1");
        }
        catch(Exception e){
            // we should not get here unless there is a bug
            Log.e("ph0wn", "check(): exception : "+e);
        }

        if (pattern.length() >= 8)
            pattern = ""; // reset pattern

        // display result
        enableAnswer(false); // player has provided one answer, we do not allow more attempts.
        debug_log("Score: "+ score);

        if (good)
            scoreTv.setText("Score: "+score+"/20");
        else
            scoreTv.setText("Wrong, it was "+PlayFrequency.getLongName(PlayFrequency.notes[currentIndex])+" - Score: "+score+"/20");
        return score;
    }

    public void enableAnswer(boolean answerEnabled) {
        guessButton.setEnabled(!answerEnabled);

        int visibility = View.INVISIBLE;
        if (answerEnabled)
            visibility = View.VISIBLE;

        buttonA.setVisibility(visibility);
        buttonAd.setVisibility(visibility);
        buttonB.setVisibility(visibility);
        buttonC.setVisibility(visibility);
        buttonCd.setVisibility(visibility);
        buttonD.setVisibility(visibility);
        buttonDd.setVisibility(visibility);
        buttonE.setVisibility(visibility);
        buttonF.setVisibility(visibility);
        buttonFd.setVisibility(visibility);
        buttonG.setVisibility(visibility);
        buttonGd.setVisibility(visibility);
        questionTv.setVisibility(visibility);

    }

    public void onClick(View view) {
        int id = view.getId();
        if (id == R.id.button) {
            guess();
        } else {
            int[] notesBtn = new int[]{R.id.buttonA, R.id.buttonAd, R.id.buttonB, R.id.buttonC, R.id.buttonCd, R.id.buttonD, R.id.buttonDd, R.id.buttonE, R.id.buttonF, R.id.buttonFd, R.id.buttonG, R.id.buttonGd};
            if (BuildConfig.DEBUG && !(notesBtn.length == 12)) {
                throw new AssertionError("Assertion failed");
            }

            for (int i = 0; i < notesBtn.length; i++) {
                if (notesBtn[i] == id) {
                    check(i);
                    break;
                }
            }
        }

    }




}