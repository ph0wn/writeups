'''
This is a fake C2 for the challenge.
It mimicks the Android/BianLian C2 but carries no malicious payload.
'''
from flask import Flask, current_app, request, jsonify, redirect, url_for
import logging
import sys

logging.basicConfig(stream=sys.stdout,
                    level=logging.DEBUG,
                    format=f'%(asctime)s %(levelname)s %(name)s %(funcName)15s() line=%(lineno)3s: %(message)s')

# there are too many werkzeug debug logs, disabling them
log = logging.getLogger('werkzeug')
log.setLevel(logging.ERROR)
app = Flask(__name__)


def check_headers(request, good_response):
    if 'Android' not in request.headers['User-Agent']:
        current_app.logger.warning(f"url={request.url} "
                                   f"user-agent={request.headers['User-Agent']} "
                                    "missing Android in User-Agent: answering 400")
        return jsonify(message='Bad Request - '
                       'Client expected to be an Android phone'), 400

    if 'Authorization' not in request.headers:
        current_app.logger.warning(f"url={request.url} "
                                   f"headers={request.headers} "
                                   "missing authorization: answering 401")
        return jsonify(message='You are not authorized'), 401

    current_app.logger.debug(f"url={request.url} data={request.data}"
                             f" --> response={good_response}")
    return good_response


@app.route('/api/v1/device/check', methods=['GET', 'POST'])
def command():
    response = {"success": True,
                "locked": False,
                "settings": {"hide_icon": True,
                             "zip_file_url": "http:\/\/dslkkskljsjlfj.online\/storage\/zip\/o0fnU9hd9i2BnXKhALsU7xmmxAz4Y2XSmerCX9Zd.zip",
                             "zip_version": ""},
                "stockInjects": ["bank.picolecroco.mc"],
                "showScreen": False}
    return check_headers(request, response)


@app.route('/api/v1/device', methods=['POST'])
def app_list():
    response = {"success": True,
                "stockInjects": ["bank.picolecroco.mc"]}
    current_app.logger.debug(f"url={request.url} data={request.data}"
                             f" --> response={response}")
    return check_headers(request, response)


@app.route('/api/v1/device/save-pin', methods=['GET', 'POST'])
@app.route('/api/v1/device/save-phone', methods=['GET', 'POST'])
@app.route('/api/v1/device/push-state', methods=['GET', 'POST'])
@app.route('/api/v1/device/server-log', methods=['GET', 'POST'])
@app.route('/api/v1/device/push-state', methods=['GET', 'POST'])
@app.route('/api/v1/device/notification', methods=['POST'])
@app.route('/api/v1/device/install', methods=['POST'])
@app.route('/api/v1/device/credentials', methods=['POST'])
@app.route('/api/v1/device/push', methods=['POST'])
@app.route('/api/v1/device/sms', methods=['POST'])
@app.route('/api/v1/display/app')
@app.route('/api/v1/sms-admin')
def answer_true():
    response = {"success": True, "next": "/api/v1/device/check"}
    current_app.logger.debug(f"url={request.url} data={request.data}"
                             f" --> response={response}")
    return response


@app.route('/api/v1/device/tw-status')
@app.route('/api/v1/device/ussd-run')
@app.route('/api/v1/device/screen', methods=['GET', 'POST'])
@app.route('/api/v1/device/lock', methods=['GET', 'POST'])
def answer_false():
    response = {"success": False, "msg": "Check /api/v1/device/check"}
    current_app.logger.debug(f"url={request.url} data={request.data}"
                             f" --> response={response}")
    return response


@app.route('/device')
@app.route('/device/check')
@app.route('/display')
@app.route('/sms-admin')
def api_redirect():
    return 'Running API v1. Use /api/v1/XXXX'


@app.route('/index.html')
@app.route('/api')
@app.route('/api/v1')
@app.route('/api/v1/index.html')
@app.route('/ph0wn')
@app.route('/about')
@app.route('/')
def welcome():
    current_app.logger.debug("Redirect to welcome page")
    return redirect(url_for('static',
                            filename='welcome.html'),
                    code=301)

@app.route('/payload')
@app.route('/flag')
def no_flag():
    return "Nice try, but I'm not giving you my flag so easily"


@app.route('/storage/zip/o0fnU9hd9i2BnXKhALsU7xmmxAz4Y2XSmerCX9Zd.zip')
def inject_zip():
    # this a zip with all the images the malware fakes for all apps
    current_app.logger.debug(f"Getting the ZIP: url={request.url}"
                             f" data={request.data}")
    return redirect(url_for('static',
                            filename='o0fnU9hd9i2BnXKhALsU7xmmxAz4Y2XSmerCX9Zd.zip'),
                    code=301)


@app.route('/storage/injects/inj/bank.picolecroco.mc/index.html')
def inject_html():
    current_app.logger.debug(f"inject_html(): url={request.url} "
                             f"data={request.data}")
    # we don't want people to guess the name 
    # of this file with /static/index.html
    return redirect(url_for('static',
                            filename='bank_pico_7104539a900f_index.html'),
                    code=301)


@app.route('/api/v1/display/icon?appId=bank.picolecroco.mc')
def inject_icon():
    current_app.logger.debug('Getting the icon')
    return redirect(url_for('static',
                            filename='bank.picolecroco.mc.png'),
                    code=301)


@app.route('/favicon.ico')
def web_icon():
    return redirect(url_for('static',
                            filename='favicon.ico'),
                    code=301)


@app.errorhandler(404)
def page_not_found(e):
    current_app.logger.debug(f"Returning 404 for url={request.url}")
    return '404 - Challenge Author was lazy and did not implemented this'


if __name__ == '__main__':
    app.run(host='127.0.0.1', port=9999, debug=True)
