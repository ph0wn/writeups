// A. Apvrille - Fortinet

var util = require('util');
var bleno = require('bleno');
var BlenoCharacteristic = bleno.Characteristic;
var initial_value = 'Decrypt the message, write the decrypted value and read back the response to flag. Encrypted message: c4d328657a9db3dfe91d3666b941b361';
var decrypted_msg = 'Good Padawan!!!!';
var flag = 'Congratz from @cryptax: Ph0wn{@f301d31a!19B7fc28_07b3eD} - Please disconnect now.';

function BeaconCharacteristic() {
    console.log('Creating beacon characteristic');
    BeaconCharacteristic.super_.call(this, {
    // 'KyloRenIsBadJedi'.encode('hex')
    uuid: '4b796c6f-5265-6e49-7342-61644a656469',
    properties: ['read', 'write'],
    onReadRequest: secretRead,
	onWriteRequest: secretWrite,
    });
    this._value = initial_value;
    this._reset = false; // we read the response
}
				     
function secretRead(offset, callback) {
    console.log('secretRead: offset', offset);
    if (this._reset) {
	console.log('secretRead: resetting value');
	this._value = initial_value;
	this._reset = false;
	bleno.disconnect();
    }
    
    var result = this.RESULT_SUCCESS;
    var data = new Buffer(this._value);

    if (this._value == flag && offset + 22 >= data.length) {
	// we reset when we have displayed the entire flag (we display by packets of 22)
	this._reset = true;
    }

    if (offset > data.length) {
	console.log('[-] secretRead: invalid offset');
	result = this.RESULT_INVALID_OFFSET;
	data = null;
    } else {
	data = data.slice(offset);
	console.log('[+] secretRead: sending back sliced data: ', data);
    }

    callback(result, data);
}


function secretWrite(data, offset, withoutResponse, callback) {
    if (offset) {
	console.log('[-] secretWrite: bad offset: data=',data, ' offset=',offset);
	callback(this.RESULT_ATTR_NOT_LONG);
    }
    else if (data.length > 0) {
	console.log('[+] secretWrite got: ', data.toString('hex'));

	if (data == decrypted_msg) {
	    console.log('[+] Congratulations! Writing flag');
	    this._value = flag;
	} else {
	    console.log('[+] Nope - bad try');
	    this._value = 'Not yet, try again...';
	}

	var data = new Buffer(this._value);
	callback(this.RESULT_SUCCESS, data);
    }
}


util.inherits(BeaconCharacteristic, BlenoCharacteristic);
module.exports = BeaconCharacteristic;

