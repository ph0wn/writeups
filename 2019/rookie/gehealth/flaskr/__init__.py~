from flask import (
    Flask, render_template, request
    )

app = Flask(__name__, instance_relative_config=True)

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/submit.html', methods=['POST'])
def submit():
    assert request.method == 'POST'
    patient_id = request.form['patient_id']
    patient_password = request.form['patient_password']
    if patient_id == 'pico' and patient_password == '19990401':
        return render_template('submit.html')
    return render_template('bad.html')

@app.errorhandler(404)
def not_found(error):
    print "not_found(): ", request.url
    return "Not found"
    

