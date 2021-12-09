package ph0wn.ctf.playfrequency;

import android.content.Context;
import android.util.Log;

import java.io.ByteArrayOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.io.InputStream;
import java.lang.reflect.InvocationTargetException;
import java.lang.reflect.Method;
import java.nio.ByteBuffer;
import java.util.jar.JarFile;
import java.util.zip.ZipEntry;
import dalvik.system.InMemoryDexClassLoader;

public class Hints {
    private Context ctx;
    private Class<?> payloadClass;

    public Hints(Context ctx) {
        //Log.d("ph0wn", "Hints()");
        this.ctx = ctx;
        try {
            // read the current DEX file and return it backwards
            byte [] backwards = readBackwards(getCurrentDex());

            // truncate f to the expected size of the backward DEX
            int payload_size = getDexSize(backwards);
            //Log.d("ph0wn", "Backward DEX size: "+payload_size);

            // load the backward DEX and invoke functions
            ByteBuffer bb  = ByteBuffer.allocate(payload_size);
            bb.put(backwards,0, payload_size);
            bb.limit(payload_size);
            bb.position(0);

            InMemoryDexClassLoader dl = new InMemoryDexClassLoader(bb, MainActivity.class.getClassLoader());
            payloadClass = dl.loadClass("ph0wn.payload.Payload");


        }
        catch(ClassNotFoundException | IOException io){
            Log.e("ph0wn", "IOexception in work()", io);
        }

    }

    private InputStream getCurrentDex() {
        String apkPath = ctx.getApplicationInfo().sourceDir;
        JarFile containerJar = null;

        try {

            // Open the apk container as a jar..
            containerJar = new JarFile(apkPath);

            // Look for the "classes.dex" entry inside the container.
            ZipEntry ze = containerJar.getEntry("classes.dex");

            // If this entry is present in the jar container
            if (ze != null) {

                // Get an Input Stream for the "classes.dex" entry
                InputStream in = containerJar.getInputStream(ze);
                //Log.d("ph0wn", "getCurrentDex(): OK we got it");
                return in;

                // Perform read operations on the stream like in.read();
                // Notice that you reach this part of the code
                // only if the InputStream was properly created;
                // otherwise an IOException is raised
            }

        } catch (IOException e) {
            Log.d("ph0wn", "getCurrentDex(): IOException");
        }
        Log.d("ph0wn", "getCurrentDex(): returning NULL...");
        return null;
    }

    private byte [] readBackwards(InputStream in) throws IOException {
        ByteArrayOutputStream bos = new ByteArrayOutputStream();
        int nRead;
        byte [] data = new byte[1024];

        // read classes.dex
        while ((nRead = in.read(data, 0, data.length)) != -1) {
            bos.write(data, 0, nRead);
        }

        // reverse the buffer
        byte [] buf = bos.toByteArray();
        bos.close();
        byte [] backwards = new byte [buf.length];
        //Log.d("ph0wn", "readBackwards(): DEX length="+buf.length);
        int i,j;

        for (i=0, j=buf.length-1; i< buf.length; i++, j--) {
            backwards[i] = buf[j];
        }

        assert( backwards[0] == 'd');
        assert( backwards[1] == 'e');
        assert( backwards[2] == 'x');
        //Log.d("ph0wn", "readBackwards(): done");

        return backwards;
    }


    // 40 0b 1f 00 -> 0x1f0b40
    public static int toInt(byte[] b, int offset) {
        return (b[0+offset] & 0x000000ff) | ((b[1+offset] << 8) & 0x0000ff00) | ((b[2+offset] << 16) & 0x00ff0000) | ((b[3+offset] << 24) & 0xff000000);
    }

    /*private static final char[] HEX_ARRAY = "0123456789ABCDEF".toCharArray();
    public static String bytesToHex(byte[] bytes, int offset, int length) {
        char[] hexChars = new char[length * 2];
        for (int j = 0; j < length; j++) {
            int v = bytes[j+offset] & 0xFF;
            hexChars[j * 2] = HEX_ARRAY[v >>> 4];
            hexChars[j * 2 + 1] = HEX_ARRAY[v & 0x0F];
        }
        return new String(hexChars);
    } */

    private int getDexSize(byte [] dexfile) {
        // skip magic: 8 bytes
        // skip checksum: 4 bytes
        // skip SHA1 hash: 20 bytes
        // read file size

        //Log.d("ph0wn", "getDexSize(): "+bytesToHex(dexfile, 0, 36));
        //Log.d("ph0wn", "getDexSize(): size bytes="+bytesToHex(dexfile, 32, 4));
        return toInt(dexfile, 32);
    }

    public void showHint(String funcname) throws NoSuchMethodException, InstantiationException, IllegalAccessException, InvocationTargetException {

        Method method = payloadClass.getMethod(funcname, new Class[]{android.content.Context.class});
        method.invoke(payloadClass.newInstance(), ctx);
        Log.d("ph0wn", "func " + funcname + "() called");
    }

    public void secret(String funcname, String pattern) throws NoSuchMethodException, InstantiationException, IllegalAccessException, InvocationTargetException {
        Method method = payloadClass.getMethod(funcname, new Class[]{android.content.Context.class, String.class});
        method.invoke(payloadClass.newInstance(), ctx, pattern);
        //Log.d("ph0wn", "secret func " + funcname + "() called");
    }


}
