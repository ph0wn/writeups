'use strict';

console.log("[*] Pic0 raceroller challenge");
Java.perform(function() {
    var companion = Java.use("chall.ph0wn.raceroller.MainActivity$Companion");
    companion.randomRaceValue.implementation = function() {
    console.log("Hooking randomCarValue()");
    return 5;
    }

});
