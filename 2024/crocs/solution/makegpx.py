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
