// A. Apvrille - Fortinet

var bleno = require('bleno');
var BeaconService = require('./beacon-service');
const util = require('util');

console.log('----- Ph0wn CTF Beacon ------');

var beaconService = new BeaconService();

bleno.on('stateChange', function(state) {
    console.log('[+] stateChange: ' + state);

    if (state === 'poweredOn') {
	var name = 'Ph0wn Beacon';
	bleno.startAdvertising(name, [beaconService.uuid], function(error) {
	    console.log('[' + (error ? '-' + error : '+') + '] startAdvertising');
	});
    } else {
	console.log('[+] Stop advertising');
	bleno.stopAdvertising();
    }
});

// Notify the console that we've accepted a connection

bleno.on('advertisingStart', function(error) {
    console.log('[' + (error ? '-' + error : '+') + '] advertisingStart');

    if (!error) {
	bleno.setServices([ beaconService ], function(error){
	    console.log('[' + (error ? '-' + error : '+') + '] setServices');
	});
    }
});

bleno.on('advertisingStop', function() {
  console.log('[+] advertisingStop');
});


// Linux only events /////////////////
bleno.on('rssiUpdate', function(rssi) {
  console.log('[+] rssiUpdate: ' + rssi);
});


bleno.on('disconnect', function(clientAddress) {
    console.log('[+] Disconnect client: ' + clientAddress);
});

bleno.on('accept', function(clientAddress) {
    console.log("[+] Accepted connection from address: " + clientAddress);

    // restricting who connects - if somebody asks for a specific time slot
    /*if (clientAddress != '78:c3:e9:7f:47:e9') { // put participants MAC address here
	console.log("[-] Get out! Disconnecting " + clientAddress)
	bleno.disconnect();
    }
    else {
    */
    // we will disconnect after 90 seconds whatever happens
    setTimeout(function(){
	console.log('[+] asking to disconnect');
	bleno.disconnect();
    }, 90 * 1000);
    //} // uncomment this for specific time slot
});

