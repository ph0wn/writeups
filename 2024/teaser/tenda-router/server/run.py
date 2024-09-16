from flask import Flask, request, redirect, send_from_directory, make_response, Response
from flask_limiter import Limiter
from flask_limiter.util import get_remote_address
from datetime import datetime
import werkzeug
import traceback
import logging
from sessions import Sessions
from limiterPrintCtrl import LimiterPrintCtrl

__author__ = "Efrén Boyarizo - modifs by Axelle Apvrille"
__license__ = "GPL"
__email__ = "efren@boyarizo.es"

username = "admin"
password = "3a2270f887b02c94126dc03b3a738a25"  # Dut!fulS0up

app = Flask(__name__)

logging.basicConfig(level=logging.INFO,
                    format='%(asctime)s- %(levelname)s -%(message)s')
sessions = Sessions()
limiterPrintControl = LimiterPrintCtrl(timeout_s=5*60)
limiter = Limiter(
    get_remote_address,
    app=app,
    default_limits=["200 per 5 minute"],
    storage_uri="memory://"
)
limiter_logger = logging.getLogger('flask-limiter')
limiter_logger.setLevel(logging.ERROR)


@app.route('/')
def index():
    return redirect('/main.html')


@app.route('/index.html')
def indexHTML():
    return redirect('/main.html')


@app.route('/flag')
def flag():
    global sessions, log
    if not sessions.check():
        return redirect('/login.html')
    now = datetime.now()
    logging.info(now.strftime("%H:%M:%S") + ": " +
             request.remote_addr + " → " + request.path)
    with open('webroot_ro/nearly_flag', 'r') as f:
        r = Response(f.read(), mimetype='text')
    return r

@app.route('/flag_b21abc907ad4742969a9970e36ecc8efa995f1720270090a3c7184abacd65061')
def real_flag():
    global sessions, log
    if not sessions.check():
        return redirect('/login.html')
    now = datetime.now()
    logging.info(now.strftime("%H:%M:%S") + ": " +
             request.remote_addr + " → " + request.path)
    with open('webroot_ro/flag', 'r') as f:
        r = Response(f.read(), mimetype='text')
    return r


@app.route('/main.html')
def mainHTML():
    global sessions
    if not sessions.check():
        return redirect('/login.html')
    return send_from_directory('webroot_ro', "main.html")


@app.route('/login/Auth', methods=["POST"])
def login():
    global sessions, username, password

    user = request.form.get("username")
    pw = request.form.get("password")
    if user != username:
        return "1"  # Incorrect user
    if pw != password:
        return "1"  # Incorrect password

    now = datetime.now()

    cookie = sessions.create(pw)
    logging.info(now.strftime("%H:%M:%S") + ": " +
                 request.remote_addr + " → " + request.path +
                 " User: {} Pw: {}".format(user, pw) +
                 " Cookie: {}".format(cookie))

    res = make_response("2")
    res.set_cookie("password", value=cookie)
    return res  # Correct password


@app.route('/goform/exit')
def logout():
    global sessions

    sessions.destroy(request.cookies.get('password'))

    return redirect('/login.html')


@app.route('/goform/GetRouterStatus')
def routerStatus():
    global sessions

    if not sessions.check():
        return redirect('/login.html')

    return {"wl5gEn": "0", "wl5gName": "", "wl24gEn": "0", "wl24gName": "", "lineup": "0|0|0|0", "usbNum": "0", "clientNum": 0, "blackNum": 0, "listNum": 0, "deviceName": "AC15", "lanIP": "192.168.100.2", "lanMAC": "52:54:00:12:34:56",
            "workMode": "router", "apStatus": "2100001", "wanInfo": [{"wanStatus": "2100001", "wanIp": "", "wanUploadSpeed": "0", "wanDownloadSpeed": "0"}], "onlineUpgradeInfo": {"newVersionExist": "0", "newVersion": "", "curVersion": "V15.03.05.18_multi"}}


@app.route('/goform/GetSysAutoRebbotCfg')
def sysAuto():
    global sessions

    if not sessions.check():
        return redirect('/login.html')

    return {"autoRebootEn": "0", "time": "03:00-05:0", "rebootTime": "03:00", "delayRebootEn": "true", "timeUp": "0", "speed": "3"}


@app.route('/<path:path>')
def static_files(path):
    return send_from_directory('webroot_ro', path)


@app.errorhandler(werkzeug.exceptions.MethodNotAllowed)
def handle_bad_request(e):
    return """<img src="https://c.tenor.com/h-PCpFxjIWAAAAAC/lotr-you-have-no-power-here.gif">"""


@app.errorhandler(Exception)
def catch(e):
    global log
    if "Not Found" in str(e):
        now = datetime.now()
        logging.info(now.strftime("%H:%M:%S") + ": " +
                 request.remote_addr + " → " + request.path + " (Not Found)")
        return "404 (Not found)", 404
    if "Many Requests" in str(e):
        if not limiterPrintControl.check():
            limiterPrintControl.create()
            logging.info("{} hit rate limit".format(request.remote_addr))
        return "429 (Too Many Requests)", 429
    logging.error("Caught exception: " + str(e))
    return "400 (Bad request)", 400


if __name__ == "__main__":
    app.run(debug=False, host="0.0.0.0", port=8080)
