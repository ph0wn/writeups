## Crocs by Letitia

### Description

Pico le Croco is a stylish crocodile who loves driving his sleek Ferrari or his luxurious Rolls Royce. He particularly enjoys visiting Cote d'Azur, one of the rare regions of France with many polyglots and delicacies. Being a sneaky hunter, he has hidden the sign warning about him, as well as his 24 stops to his destination.

Participants are provided a file `ph0wncrocs.zip`

### Solution

Pico is a sneaky crocodile. Can you figure out the key and his next meal?

The challenge is a zip with a text file and crocodile pictures. Keep the
text file and its name in mind since it contains hints about the key when
you find it.

But if you look at the zip file, you’ll notice it starts with “%PDF”. This
file is a polyglot, or file that is valid in multiple formats. Rename the
zip to .pdf, and open it to see something entirely different.

All you see is a single page pdf with a crocodile who is on the move. But if
you look at the file in detail, you notice many page entries beyond the
single page. If you go to the /Pages object, you’ll notice that there are
actually 27 Page objects linked, even if the /Length field is set to 1. Edit
the file in hexedit to change the 1 to a 27, and you’ll see many more
crocodiles and list of coordinates. Pico doesn’t like the sign warning his
food about him.

```
/Type /Pages
/Kids [ 4 0 R 5 0 R 6 0 R 7 0 R 8 0 R 9 0 R 10 0 R 11 0 R 12 0 R 13 0 R 14 0 R 15 0 R 16 0 R 17 0 R 18 0 R 19 0 R 20 0 R 21 0 R 22 0 R 23 0 R 24 0 R 25 0 R 26 0 R 27 0 R 28 0 R 29 0 R 30 0 R ]
/Count  1 >>
```

Change `/Count 1` to `/Count 27`.


By using pdftotext, you can easily extract all the sets of coordinates.

```
$ pdftotext ph0wncrocs.zip
```

With some processing from a library like JPX, the list of coordinates can be
turned into a gpx file.

> @cryptax: or ask ChatGPT to write a script to transform a list of coordinates in a CSV file to a GPX


```python
import csv

# Specify the input CSV file name
input_csv_file = 'coordinates.csv'

# Specify the output GPX file name
output_gpx_file = 'output.gpx'

# Initialize an empty list to hold all tracks
tracks = []
current_track = []

# Read the CSV file
with open(input_csv_file, 'r') as csvfile:
    reader = csv.reader(csvfile)
    
    # Skip the header row
    next(reader, None)
    
    for row in reader:
        # Check if the row is empty (a blank line)
        if not row or not any(row):
            # If there's a current track being built, save it and start a new one
            if current_track:
                tracks.append(current_track)
                current_track = []
        else:
            # Extract latitude and longitude
            lat = float(row[0])
            lon = float(row[1])
            current_track.append((lat, lon))

    # After the loop, make sure to add the last track if it exists
    if current_track:
        tracks.append(current_track)

# Write the GPX file
with open(output_gpx_file, 'w') as file:
    # Write the GPX header
    file.write('<?xml version="1.0" encoding="UTF-8"?>\n')
    file.write('<gpx version="1.1" creator="YourAppName" xmlns="http://www.topografix.com/GPX/1/1">\n')
    
    # Loop through the tracks and create track segments
    for track_idx, track in enumerate(tracks, start=1):
        file.write(f'    <trk>\n')
        file.write(f'        <name>Track {track_idx}</name>\n')
        file.write(f'        <trkseg>\n')
        for lat, lon in track:
            file.write(f'            <trkpt lat="{lat}" lon="{lon}"></trkpt>\n')
        file.write(f'        </trkseg>\n')
        file.write(f'    </trk>\n')
    
    # Write the GPX footer
    file.write('</gpx>\n')

print(f"GPX file '{output_gpx_file}' generated successfully!")
```

Then, open the gpx file with an online viewer (@cryptax: such as https://gpx.studio), and the tracks form `ph0wn{PICOVISITS_______}`. As previously hinted, the key is
all caps and 24 characters long. To find what the blanks mean, look around
and see what the single coordinate points to, which is **EURECOM**. Now is
probably a good time to work from home until Pico has eaten!

