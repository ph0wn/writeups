from flask import Flask, render_template, request, redirect, send_from_directory
from datetime import datetime
from flask_limiter import Limiter
from flask_limiter.util import get_remote_address
import logging
import csv
import hashlib
import re
import pytz

app = Flask(__name__)

# initialize logging
logging.basicConfig(level=logging.DEBUG,
                    format='%(asctime)s- %(levelname)s -%(message)s')

# Initialize the rate limiter
limiter = Limiter(
    get_remote_address,
    app=app,
    default_limits=["5 per minute"]  # Default rate limit for all routes
)

# File to store the records
# Each record are stored in UTC+0 time
records_file = '/app/logs/success_records.csv'

correct_flag_hashes = [ "7159ce222798f646fef4713c4b287b08cff153587a9e35e990669ff20d9c0a8e", "e0957014be53416c880a8c7e5b4b4cd55a5f4c0a6741dfdc91249c1bd2c06abe", "d97ce84e482660172d140ffaa6df793b62a9db721adec2a4a8641999e0677cf0" ]


def read_records():
    records = []
    utc_tz = pytz.timezone('UTC')
    paris_tz = pytz.timezone('Europe/Paris')
    try:
        with open(records_file, newline='') as csvfile:
            reader = csv.DictReader(csvfile)
            for row in reader:
                # Records are in UTC+0 timezone
                utc_time = datetime.strptime(row['time'], '%Y-%m-%d %H:%M:%S')
                utc_time = utc_tz.localize(utc_time)
                
                # Convert to Paris timezone
                paris_time = utc_time.astimezone(paris_tz)
                row['time'] = paris_time.strftime('%Y-%m-%d %H:%M:%S')
                logging.debug(f"Read record: time={row['time']} nick={row['nickname']} stage={row['stage']}")
                records.append(row)
    except FileNotFoundError:
        pass
    return records


# Helper function to write a new record to the file
def write_record(nickname: str, time, stage: int):
    # nickname of the team/individual who flagged
    # time: datetime.datetime object when submitted
    # stage number: which stage was flagged
    utc_tz = pytz.timezone('UTC')
    
    if time.tzinfo is None:
        # If the datetime object is naive, assume it's in local timezone and convert it to UTC
        local_tz = pytz.timezone('Europe/Paris')  # assuming local time is Paris time
        local_time = local_tz.localize(time)
        utc_time = local_time.astimezone(utc_tz)
    else:
        # If the datetime object is aware, convert it to UTC
        utc_time = time.astimezone(utc_tz)
        
    logging.debug(f'Writing record nick={nickname} time={utc_time} stage={stage}')
    assert stage >= 1 and stage <= 3
    with open(records_file, 'a', newline='') as csvfile:
        fieldnames = ['nickname', 'time', 'stage']
        writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
        # Write header only if the file is empty
        if csvfile.tell() == 0:
            writer.writeheader()
        writer.writerow({'nickname': nickname,
                         'time': utc_time.strftime('%Y-%m-%d %H:%M:%S'),
                         'stage': stage})


def sanitize_string(input_string):
    # Define the regular expression pattern to match allowed characters
    pattern = r'[^a-zA-Z0-9@#$!_-éèàçôûùêëï ]'
    # Substitute all disallowed characters with an empty string
    sanitized_string = re.sub(pattern, '', input_string)
    return sanitized_string


@app.route('/hurrayifoundtheflag/submit', methods=['GET', 'POST'])
@limiter.limit("3 per minute")  # Rate limit for the /submit endpoint
def submit():
    if request.method == 'POST':
        nickname = sanitize_string(request.form['nickname'])
        flag = request.form['flag']
        flag_hash = hashlib.sha256(flag.encode()).hexdigest()
        for i in range(0, len(correct_flag_hashes)):
            if flag_hash == correct_flag_hashes[i]:
                # If the flag is correct, add the nickname and
                # current time to the success_records
                write_record(nickname, datetime.now(), i+1)
                return redirect('/')
        # Incorrect flag
        return render_template('submit.html', message='Incorrect flag.')

    return render_template('submit.html')

@app.route('/673d27d84d17ef194b0dbe4ac02d85a40d75d8e12310cdd538551bef0fecc333')
def stage2():
     return send_from_directory('static/', 'stage2.tar.gz')

@app.route('/.well-known/security.txt')
def security():
    return send_from_directory('static/', 'security.txt')
 
@app.route('/')
def home():
    # Sort success_records by time in ascending order
    sorted_records = sorted(read_records(), key=lambda x: x['time'])
    return render_template('home.html', records=sorted_records)


if __name__ == '__main__':
    app.run(host='0.0.0.0', debug=False, port=8090)
