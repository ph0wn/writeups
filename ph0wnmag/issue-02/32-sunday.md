## Sunday Training by Pr TTool

You are provided with a GPX trace - nothing out of the ordinary. If you open it, for example in Google Earth, you'll notice that the trace follows the famous path around Cap d'Antibes. The locations and altitudes seem accurate, so everything looks fine at first glance.

However, when examining the elevation profile, speed, and heart rate data, you'll notice something unusual: the heart rate values seem abnormal.


![Screen capture in google earth](./images/heartrate.png)

In fact, an ASCII stream of characters is hidden within the hr element of the extension elements in the GPX trace. "hr" stands for "heart rate," which is a very common extension element in GPX files.

```xml

  <trkpt lat="43.553913" lon="7.137028">
                <ele>0.000000</ele>
                <time>2024-10-15T09:37:40Z</time>
                <extensions>
                    <gpxtpx:TrackPointExtension>
                        <gpxtpx:hr>112</gpxtpx:hr>
                    </gpxtpx:TrackPointExtension>
                </extensions>
            </trkpt>
            <trkpt lat="43.553920" lon="7.137040">
                <ele>0.000000</ele>
                <time>2024-10-15T09:37:44Z</time>
                <extensions>
                    <gpxtpx:TrackPointExtension>
                        <gpxtpx:hr>104</gpxtpx:hr>
                    </gpxtpx:TrackPointExtension>
                </extensions>
			</trkpt>
```
To solve this, the approach is to load the GPX file and extract the values of the gpxtpx:hr element:


```bash
$ grep -o '<gpxtpx:hr>[0-9]\+</gpxtpx:hr>' walk.gpx | sed 's/<[^>]*>//g' | while read -r ascii; do printf "\\$(printf '%03o' "$ascii")"; done; echo
```
