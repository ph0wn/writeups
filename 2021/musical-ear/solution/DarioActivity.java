package com.example.myapplication;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.view.MotionEventCompat;
import androidx.core.view.ViewCompat;

import android.content.Context;
import android.os.Build;
import android.os.Bundle;
import android.util.Log;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.lang.reflect.Method;
import java.nio.ByteBuffer;
import java.util.jar.JarFile;
import java.util.zip.ZipEntry;

import dalvik.system.InMemoryDexClassLoader;

public class MainActivity extends AppCompatActivity {

    private Class<?> CanYouWin;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);
        initPhown();
        Method ABCdF = null, EFABC = null, FGFGB = null;
        Context context = getApplicationContext();
        for (Method d : this.CanYouWin.getDeclaredMethods()) {
            Log.e("asd", d.getName());
            if (d.getName().equals("FGFGB")) FGFGB = d;
            if (d.getName().equals("ABCdF")) ABCdF = d;
            if (d.getName().equals("EFABC")) EFABC = d;
        }
        try {
            ABCdF.invoke(this.CanYouWin.newInstance(), context);
            EFABC.invoke(this.CanYouWin.newInstance(), context);
            FGFGB.invoke(this.CanYouWin.newInstance(), context);
        } catch (Exception e)
        {}
    }

    public final void initPhown() {
        try {
            byte[] CanYouWin2 = CanYouWin(WeLovePh0wn());
            int i = (CanYouWin2[32] & 255) | ((CanYouWin2[33] << 8) & MotionEventCompat.ACTION_POINTER_INDEX_MASK) | ((CanYouWin2[34] << 16) & 16711680) | ((CanYouWin2[35] << 24) & ViewCompat.MEASURED_STATE_MASK);
            ByteBuffer allocate = ByteBuffer.allocate(i);
            allocate.put(CanYouWin2, 0, i);
            allocate.limit(i);
            allocate.position(0);
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                this.CanYouWin = new InMemoryDexClassLoader(allocate, MainActivity.class.getClassLoader()).loadClass("ph0wn.payload.Payload");
            }
        } catch (IOException | ClassNotFoundException e) {
            Log.e("ph0wn", "IOexception in work()", e);
        }

    }

    public final InputStream WeLovePh0wn() {
        try {
            return getAssets().open("ph0wn.dex");
        } catch (IOException unused) {
            Log.d("ph0wn", "getCurrentDex(): IOException");
        }
        Log.d("ph0wn", "getCurrentDex(): returning NULL...");
        return null;
    }

    public final byte[] CanYouWin(InputStream inputStream) throws IOException {
        int i;
        ByteArrayOutputStream byteArrayOutputStream = new ByteArrayOutputStream();
        byte[] bArr = new byte[1024];
        while (true) {
            i = 0;
            int read = inputStream.read(bArr, 0, 1024);
            if (read == -1) {
                break;
            }
            byteArrayOutputStream.write(bArr, 0, read);
        }
        byte[] byteArray = byteArrayOutputStream.toByteArray();
        byteArrayOutputStream.close();
        byte[] bArr2 = new byte[byteArray.length];
        int length = byteArray.length - 1;
        while (i < byteArray.length) {
            bArr2[i] = byteArray[length];
            i++;
            length--;
        }
        return bArr2;
    }

}
