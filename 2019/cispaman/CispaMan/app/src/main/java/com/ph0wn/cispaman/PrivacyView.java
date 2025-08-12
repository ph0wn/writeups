package com.ph0wn.cispaman;

import android.content.Context;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.graphics.Canvas;
import android.graphics.Color;
import android.graphics.Paint;
import android.graphics.Rect;
import android.util.AttributeSet;
import android.view.View;

import java.io.InputStream;
import java.nio.ByteBuffer;
import java.nio.ByteOrder;
import java.util.ArrayList;
import java.util.Arrays;

public class PrivacyView extends View {
    private Context ctx;
    private Paint blackPaint = new Paint();
    private ArrayList<Paper> papers = new ArrayList<Paper>();

    public PrivacyView(Context context) {
        this(context, null);
    }

    public PrivacyView(Context context, AttributeSet attrs) {
        super(context, attrs);
        this.ctx = context;
        blackPaint.setStyle(Paint.Style.FILL_AND_STROKE);
    }

    @Override
    protected void onSizeChanged(int w, int h, int oldw, int oldh) {
        super.onSizeChanged(w, h, oldw, oldh);
        getItems();
        invalidate();
    }


    @Override
    protected void onDraw(Canvas canvas) {
        canvas.drawColor(Color.BLACK);

        for (Paper x : papers) {
//            Log.e("MOBISEC", "About to draw: " + x.title + " " + x.authors + " " + x.conf + " " + x.year + " "  + x.citations);
            Bitmap bb = BitmapFactory.decodeResource(getResources(), x.citations);
            if (bb != null) {
                canvas.drawBitmap(bb, null, new Rect(x.title, x.authors, x.conf, x.year), blackPaint);
            } else {
//                Log.e("MOBISEC", "ERROR could not find item " + x.citations);
            }
        }
    }

    private void getItems() {
        papers.clear();

        try {
            InputStream inputStream = ctx.getAssets().open("cm.dat");

            float coords[] = new float[4];
            byte curr[] = new byte[4];
            byte rem[] = new byte[4];

            byte[] mask = {99, 105, 115, 112, 97, 109, 97, 110, 104, 97, 115, 101, 118, 101, 114, 121, 102, 108, 97, 103};
            byte[] nextMask = {0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0};

            while (true) {
                int ret = -1;
                for (int i = 0; i < 4; i++) {
                    ret = inputStream.read(curr, 0, 4);
                    if (ret < 0) break;
                    ret = inputStream.read(rem, i, 1);
                    if (ret < 0) break;
                    shield(curr, curr, Arrays.copyOfRange(mask, i*5, i*5+4));
                    rem[i] = (byte) ( ((int) rem[i]) ^ ((int) mask[i*5+4]));
                    for (int j=0; j<4; j++) {
                        nextMask[5*i+j] = curr[j];
                    }
                    nextMask[5*i+4] = rem[i];
                    coords[i] = ByteBuffer.wrap(curr).order(ByteOrder.LITTLE_ENDIAN).getFloat();
                }
                if (ret < 0) break;
                int resId = ByteBuffer.wrap(rem).order(ByteOrder.LITTLE_ENDIAN).getInt();
                for (int i = 0; i < 20; i++) {
                    mask[i] = nextMask[i];
                }
                papers.add(new Paper((int) (coords[0]*getWidth()),(int) (coords[1]*getHeight()), (int) (coords[2]*getWidth()), (int) (coords[3]*getHeight()), resId));
//                Log.e("MOBISEC", "parsed item: " + coords[0] + " " + coords[1] + " " + coords[2] + " " + coords[3] + " " + citations);
            }

        } catch (Exception ex) {
            ex.printStackTrace();
        }
    }

    private void shield(byte[] out, byte[] in1, byte[] in2) {
        for (int i=0; i<in1.length; i++) {
            out[i] = (byte) (((int)in1[i]) ^ ((int)in2[i]));
        }
    }
}
