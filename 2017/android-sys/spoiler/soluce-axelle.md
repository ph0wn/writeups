I intentionally tried to solve this challenge doing some reversing.

After a quick overview, we see: 

- MainActivity: starts BackgroundService and removes the app icon
- StartMyServiceAtBootReceiver: starts BackgroundService
- a: logs in adb logcat a message "interrupted" + something
- BackgroundService: tons of fake useless stuff, but that's where we need to investigate.

Using androguard, I reverse `onCreate()` method of BackgroundService:

```java
public void onCreate()
    {
        this.a = new android.os.Handler();
        this.q = this.a(new StringBuilder().append(this.i).append(this.e).toString(), new StringBuilder().append(this.b).append(this.d).toString().toLowerCase());
        byte[] v5 = this.q.getBytes();
        this.g = new StringBuilder();
        int v6 = v5.length;
        int v4 = 0;
        while (v4 < v6) {
            int v2_8 = 0;
            int v3_2 = v5[v4];
            while (v2_8 < 8) {
                if (v2_8 > 0) {
                    int v0_19;
                    if ((v3_2 & 128) != 0) {
                        v0_19 = 1;
                    } else {
                        v0_19 = 0;
                    }
                    this.g.append(v0_19);
                }
                v3_2 <<= 1;
                v2_8++;
            }
            v4++;
        }
        this.m = this.b(this.o, this.p);
        this.a.postDelayed(new ph0wn.android.sys.BackgroundService$1(this), 10000);
        return;
    }
```

The beginning is useless, we are mainly interested to print `this.m`:

```java
this.m = this.b(this.o, this.p);
this.a.postDelayed(new ph0wn.android.sys.BackgroundService$1(this), 10000);
```

I patch the smali of the application to add a log to display `m`:

```
  invoke-virtual {p0, v0, v1}, Lph0wn/android/sys/BackgroundService;->b(Ljava/lang/String;Ljava/lang/String;)Ljava/lang/String;

    move-result-object v0

    const-string v5, "AXELLE2"

    invoke-static {v5, v0}, Landroid/util/Log;->d(Ljava/lang/String;Ljava/lang/String;)I

    iput-object v0, p0, Lph0wn/android/sys/BackgroundService;->m:Ljava/lang/String;
```

Then, I recompile (smali), repack (zip), sign (jarsigner) the application and launch it.

```
# adb logcat | grep AXELLE
D/AXELLE2 ( 1302): 10100001101000011000011101111101110111101101101001101110011001111001001110010100111101100011000100011010001100111110110011001110100101111101
```

If we take bytes, this doesn't show anything interesting: 
`0xa1a1877ddeda6e679394f6311a33ecce97dL` where 0xa1 is not an ASCII character.

I got a hint at that point, Ludovic telling me its encoded on 7 bits.

```python
binary_stream = '10100001101000011000011101111101110111101101101001101110011001111001001110010100111101100011000100011010001100111110110011001110100101111101'
chr(int(binary_stream[:7],2))
```
the first character is `P` (for Ph0wn) which is a good start.


We code that:

```python
bitstream = '10100001101000011000011101111101110111101101101001101110011001111001001110010100111101100011000100011010001100111110110011001110100101111101'

index = 0
solution = ''

while index < len(bitstream):
    solution = solution + chr(int(bitstream[index:index+7],2))
    index = index + 7

print solution
```

and get the flag `Ph0wn{4n3drO1D43v3R}`
