package ph0wn.ctf.playfrequency;

import android.media.AudioFormat;
import android.media.AudioManager;
import android.media.AudioTrack;
import android.util.Log;
import android.widget.Toast;

public class PlayFrequency {
    // from https://stackoverflow.com/questions/9106276/android-how-to-generate-a-frequency
    private final static int DURATION = 3; // seconds
    private final static int SAMPLE_RATE = 8000;
    private final static int NUM_SAMPLES = DURATION * SAMPLE_RATE;
    private final static double[] sample = new double[NUM_SAMPLES];
    private final static byte[] generatedSnd = new byte[2 * NUM_SAMPLES];
    public final static double [] notes = new double [] {440, 466.164, 493.883, 523.251, 554.365, 587.33, 622.254, 659.255, 698.456, 739.989, 783.991, 830.609, 880, 932.3275, 987.7666, 1046.502, 1108.731, 1174.659, 1244.508, 1318.510, 1396.913 , 1479.978, 1567.982 , 1661.219  };

    static void generateTone(double freqOfTone){
        // fill out the array
        for (int i = 0; i < NUM_SAMPLES; ++i) {
            sample[i] = Math.sin(2 * Math.PI * i / (SAMPLE_RATE/freqOfTone));
        }

        // convert to 16 bit pcm sound array
        // assumes the sample buffer is normalised.
        int idx = 0;
        for (final double dVal : sample) {
            // scale to maximum amplitude
            final short val = (short) ((dVal * 32767));
            // in 16 bit wav PCM, first byte is the low order byte
            generatedSnd[idx++] = (byte) (val & 0x00ff);
            generatedSnd[idx++] = (byte) ((val & 0xff00) >>> 8);
        }
    }

    /* returns true on success, false if unable to play */
    static boolean playSound(double freqOfTone){
        AudioTrack audioTrack = new AudioTrack(AudioManager.STREAM_MUSIC, /* stream type */
                    SAMPLE_RATE,
                    AudioFormat.CHANNEL_OUT_MONO,
                    AudioFormat.ENCODING_PCM_16BIT,
                    NUM_SAMPLES,
                    AudioTrack.MODE_STATIC);
        generateTone(freqOfTone);
        int nb = audioTrack.write(generatedSnd, 0, generatedSnd.length);
        if (nb < 0){
            Log.e("ph0wn", "playSound(): write() returned: "+nb);
            return false;
        }

        audioTrack.play();
        return true;
    }

    static String getLongName(double freqOfTone) {

        if (freqOfTone == notes[0]) return "A4 - La";
        if (freqOfTone == notes[1]) return "A#4 - La#";
        if (freqOfTone == notes[2]) return "B4 - Si";
        if (freqOfTone == notes[3]) return "C5 - Do";
        if (freqOfTone == notes[4]) return "C#5 - Do#";
        if (freqOfTone == notes[5]) return "D5 - Ré";
        if (freqOfTone == notes[6]) return "D#5 - Ré #";
        if (freqOfTone == notes[7]) return "E5 - Mi";
        if (freqOfTone == notes[8]) return "F5 - Fa";
        if (freqOfTone == notes[9]) return "F#5 - Fa #";
        if (freqOfTone == notes[10]) return "G5 - Sol";
        if (freqOfTone == notes[11]) return "G#5 - Sol #";
        if (freqOfTone == notes[12]) return "A5 - La";
        if (freqOfTone == notes[13]) return "A#5 - La#";
        if (freqOfTone == notes[14]) return "B5 - Si";
        if (freqOfTone == notes[15]) return "C6 - Do";
        if (freqOfTone == notes[16]) return "C#6 - Do#";
        if (freqOfTone == notes[17]) return "D6 - Ré";
        if (freqOfTone == notes[18]) return "D#6 - Ré #";
        if (freqOfTone == notes[19]) return "E6 - Mi";
        if (freqOfTone == notes[20]) return "F6 - Fa";
        if (freqOfTone == notes[21]) return "F#6 - Fa #";
        if (freqOfTone == notes[22]) return "G6 - Sol";
        if (freqOfTone == notes[23]) return "G#6 - Sol #";
        return "Unknown";
    }

    static String getShortName(double freqOfTone) {
        if (freqOfTone == notes[0]) return "A4";
        if (freqOfTone == notes[1]) return "Ad4";
        if (freqOfTone == notes[2]) return "B4";
        if (freqOfTone == notes[3]) return "C5";
        if (freqOfTone == notes[4]) return "Cd5";
        if (freqOfTone == notes[5]) return "D5";
        if (freqOfTone == notes[6]) return "Dd5";
        if (freqOfTone == notes[7]) return "E5";
        if (freqOfTone == notes[8]) return "F5";
        if (freqOfTone == notes[9]) return "Fd5";
        if (freqOfTone == notes[10]) return "G5";
        if (freqOfTone == notes[11]) return "Gd5";
        if (freqOfTone == notes[12]) return "A5";
        if (freqOfTone == notes[13]) return "Ad5";
        if (freqOfTone == notes[14]) return "B5";
        if (freqOfTone == notes[15]) return "C6";
        if (freqOfTone == notes[16]) return "Cd6";
        if (freqOfTone == notes[17]) return "D6";
        if (freqOfTone == notes[18]) return "Dd6";
        if (freqOfTone == notes[19]) return "E6";
        if (freqOfTone == notes[20]) return "F6";
        if (freqOfTone == notes[21]) return "Fd6";
        if (freqOfTone == notes[22]) return "G6";
        if (freqOfTone == notes[23]) return "Gd6";
        return "U";
    }
}

