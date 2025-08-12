from flask import Flask, render_template, request, send_from_directory, session
import logging
import os
import configparser
from gevent.pywsgi import WSGIServer

PORT = 1234
app = Flask(__name__, instance_relative_config=True)
app.secret_key = 'AKJu377194$#1v9249214s938923849!08we/uyeuqywe'
logging.basicConfig(level=logging.DEBUG,format="%(asctime)s:%(levelname)s:%(funcName)20s() %(lineno)s: %(message)s")



class JumpDevice():
    def __init__(self, config_file='att.cfg'):
        self.config = configparser.ConfigParser()
        self.config.read(config_file)

    def get_data(self, handle):
        # returns { 'type' : 'service', 'uuid' : '1800', 'endhandle' : '0x0009' ... }
        return self.config[handle]

    def get_service_uuids(self):
        '''
        returns a list of service UUID strings. 
        Those strings are returned lower case, but potentially with separators (-) if present in the config file
        '''
        services = []
        for handle in self.config.sections():
            if self.config.get(handle, 'type') == 'service':
                services.append(self.config.get(handle, 'uuid').lower())
        logging.debug("services={}".format(services))
        return services

    def get_handle(self, uuid):
        # we accept UUIDs with any case and with - as separator if needed
        for handle in self.config.sections():
            if self.config.get(handle, 'uuid').lower().replace('-','') == uuid.lower().replace('-',''):
                return handle
        logging.error("could not find uuid={}".format(uuid))
        return None

    def get_characteristic_uuids(self, service_uuid):
        # returns lowercase UUIDs with potential separators (-)
        characteristics = []
        service_handle = self.get_handle(service_uuid)
        end_handle = self.config.get(service_handle, 'endhandle')
        if service_handle == None or end_handle == None:
            logging.error("impossible to find begin/end handles")
            return None

        for h in range(int(service_handle,16)+1, int(end_handle,16)):
            logging.debug("Inspecting handle 0x{:04x}".format(h))
            try:
                uuid = self.config.get('0x{:04x}'.format(h), 'uuid').lower()
                if self.config.get('0x{:04x}'.format(h), 'type') == 'characteristic':
                    characteristics.append( uuid )
                else:
                    logging.warning("Handle 0x{:04x} is not a characteristic".format(h))
            except configparser.NoSectionError as e:
                logging.warning("Handle 0x{:04x} not found".format(h))
                
        logging.debug("service={} charact={}".format(service_uuid, characteristics))
        return characteristics

    def get_characteristic_perms(self, charac_uuid):
        # returns lower case permissions, typically read or write
        try:
            handle = self.get_handle(charac_uuid)
            perms = self.config.get(handle, 'permissions')
            permissions = perms.lower().split(',')
            return permissions
        except configparser.NoSectionError as e:
            logging.error("cannot find permissions for this characteristic {}".format(charac_uuid))
            return None

    def get_value(self, charac_uuid):
        try:
            handle = self.get_handle(charac_uuid)
            value = self.config.get(handle, 'value')
            return value
        except configparser.Error as e:
            logging.warning("could not find any hard coded value for this characteristic {}".format(charac_uuid))
            return '== EMPTY =='

def clean_session():
    session.pop('check', None)
    session.pop('service_uuid', None)
    session.pop('characteristic_uuid', None)
    logging.debug("cleaning session")


@app.route('/favicon.ico')
def favicon():
    return send_from_directory(os.path.join(app.root_path, 'static'), 'images/favicon.ico')

@app.route('/', methods=['GET'])
def select_service(infos=None):
    clean_session()
    services = jump.get_service_uuids()
    return render_template('index.html', services = services, info = infos)

@app.route('/service', methods=['POST'])
def select_characteristic():
    logging.debug("form={}".format(request.form))
    service_uuid = request.form.get("service_uuid")
    session['service_uuid'] = service_uuid
    logging.debug("service_uuid={}".format(service_uuid))
    charac = jump.get_characteristic_uuids(service_uuid)
    return render_template('service.html', service = service_uuid, characteristics = charac)

@app.route('/charac', methods=['POST'])
def select_permission():
    charac_uuid = request.form.get("charac_uuid")
    session['characteristic_uuid'] = charac_uuid
    logging.debug("charac_uuid={}".format(charac_uuid))
    perms = jump.get_characteristic_perms(charac_uuid)
    return render_template('charac.html', characteristic = charac_uuid, perms = perms)
    
@app.route('/perms', methods=['POST'])
def do_action():
    action = request.form.get("perm")
    logging.debug('action={}'.format(action))
    if not 'characteristic_uuid' in session:
        logging.error("UUID for characteristic is not set. We should not be here")
        return select_service(infos='Internal error. Start again')
    
    if action == 'read':
        if session['characteristic_uuid'].lower().replace('-','') == 'deadbeef-ff11-aadd-0000-000100000002'.replace('-',''):
            logging.debug("asking to read the flag characteristic")
            return read_flag()
        else:
            value = jump.get_value(session['characteristic_uuid'])
            logging.debug("asking to read charact_uuid={}: value={}".format(session['characteristic_uuid'], value))
            return render_template('read.html', value=value)
        
    elif action == 'write' or action == 'write without response':
        return render_template('write.html', uuid =  session['characteristic_uuid'])

    return select_service(infos='Error: unknown action. Start again.')
    

@app.route('/write', methods=['POST'])
def do_write():
    data = request.form.get("writevalue")
    logging.debug("writevalue={}".format(data))
    
    if not 'characteristic_uuid' in session:
        logging.error("UUID for characteristic is not set. We should not be here")
        return select_service(infos='Internal error. Start again')
    
    if session['characteristic_uuid'].lower().replace('-','') ==  '00005302000000414c50574953450000':
            session['check'] = data.lower().replace(' ', '').replace('0x', '')
            logging.debug("session check={}".format(session['check']))

    services = jump.get_service_uuids()
    return render_template('index.html', services = services, info = 'Write OK')

def read_flag():
    # CRC-16/MODBUS https://crccalc.com/
    # count down to target jumps 1337 = 0x539
    if 'check' in session and session['check'].lower() == '0200058100000539db3e':
        clean_session()
        logging.info("FLAG!")
        return render_template('read.html', value='ph0wn{weSeeYouRF1t_GooD}')

    if 'check' in session:
        logging.debug("wrong command ({}) - we do not show the flag".format(session['check']))
    else:
        logging.debug("there is no session[check]")
        
    return render_template('read.html', value='Find the flag!')
        


jump = JumpDevice()

if __name__ == "__main__":
    logging.info("Running WSGI server on port {}".format(PORT))
    #app.run(host='127.0.0.1', port=PORT, debug = True)
    http_server = WSGIServer(('', PORT), app)
    http_server.serve_forever()

