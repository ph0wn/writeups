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
    logon = request.form['logon']
    password = request.form['password']
    
    if (logon == 'insite' and password == '2getin') or (logon == 'xruser' and password == '4$xray') or (logon == 'root' and password == '#superxr'):
        return render_template('submit.html')
    return render_template('bad.html')

@app.errorhandler(404)
def not_found(error):
    print "not_found(): ", request.url
    return "Not found"
    

