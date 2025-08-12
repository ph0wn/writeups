// A. Apvrille - Fortinet

var util = require('util');
var bleno = require('bleno');
var BlenoPrimaryService = bleno.PrimaryService;
var BeaconCharacteristic = require('./beacon-characteristic');

function BeaconService() {
    console.log('Creating Beacon Service');
    BeaconService.super_.call(this, {
	// 'Decrypt AES msg'.encode('hex') + \00
	uuid: '4465637279707420414553206d736700',
	characteristics: [ new BeaconCharacteristic() ]
    });
}


util.inherits(BeaconService, BlenoPrimaryService);

module.exports = BeaconService;
