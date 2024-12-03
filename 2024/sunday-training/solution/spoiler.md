An, ascii stream of characters is hidden into the hrElement of the extension elements of the GPX trace. "hr" stands for "heart rate", a very common extension element.

So, the solution consists in loading the GPX and extracting values of gpxtpx:hr

Opening the file with e.g. google earth (see heartrate.png) shows that the heart rate is abnormal.

To get the flag, one just need to extrat the heart rate values and convert their ascii values into a string to get the flag.

$ make solution
