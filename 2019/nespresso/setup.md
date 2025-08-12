# Setup for ph0wn organizers

- Install the coffee machine close to the organizer's desk, in a visible area.
- Fill the water tank.
- Empty the capsule container
- Empty the drip tray.
- Put the marked cup beneath the arm
- Ensure a sponge isn't too far away
- Display a few coffee capsules nearby
- Connect **smartphone** with Nespresso app and perform a **Factory Reset**

## Re-init

After a team has scored, or if they have left the coffee machine in a strange status, it might be necessary to perform a Factory Reset with the smartphone.


## Validation

- The team must show they remotely brewed the coffee (you must ascertain they did not use the buttons!).
- They are allowed (and will have to touch) the slider.
- Normally, the challenge cannot be done with the regular app.

The challenge being difficult, we may choose to validate any team which shows:

1. They are able to brew coffee remotely, not using the app.
2. AND they are able to configure cup size.

If the team shows the ability to do both, we can be smooth on the result. For instance, be aware that the volume the coffee machine will produce is not entirely exact.

**Potential cheat (and how to detect it):**

A cheating team might try to brew successively a Ristretto (25mL) and then an Espresso (40mL). That will be 65 mL, not 90 mL. There is no way, without configuring cup sizes, to exactly get 90 mL - which is what we want.

Solution to detect:

(a) You can ask the team how they did it.
(b) If they brew successively Ristretto and Espresso, there will be a stop in the middle. You'll notice it. You can then either argue we want 70mL in a single shot (implicit) or that they haven't given exactly 70mL. If they show to you they have configured cup size, then, you should probably accept the solution.
(c) Check with the marked cup.

## Tests

If we need to test the challenge still works:

- Connect on to the Raspberry Pi 3
- Make sure Bluetooth 5.50 is installed: `bluetoothctl -v`
- Install gatttool: `sudo apt install bluez` (5.48-0ubuntu3.2)
- Go to ./nespresso and run: ./ph0wn-hack.sh. This should brew a 90mL if you have opened/closed the slider.
- Reset with `./ph0wn-reset.sh` afterwards, then open/close slider, and test ./ph0wn-lungo.sh. We should have a real lungo.


## Possible Hints

- Easy. Provide link for Nespresso App: https://play.google.com/store/apps/details?id=com.nespresso.activities
- Intermediate. Suggest to locate authorization code in BLE packets. The authorization code changes at each factory reset.
- Difficult. Suggest to look for CupSizeOperations class in the code.


## Troubleshooting

BLE is quite painful to work with.

- I noticed that some OS do not correctly initialize BLE dongles and then strange issue occur like impossible to pair. The workaround is to **power off/on** the dongle, then **scan**, then pair.

- It happens pretty often that BLE connection fails. You may have to re-try a couple of times before success.

- You cannot connect (BLE) if somebody else is already connected. Fortunately, connection don't last long + teams will need a time slot.

- You cannot brew a coffee if the slider hasn't been opened and closed.

- If BLE issues persist, you can also remove and replace the USB BLE dongle.

- If the coffee machine is in a bad state, you can try Factory Reset, Power Off/On, or using the smartphone's app.

- It is definetely possible to have several devices paired with the coffee machine. However, it may not be possible to have several active connections at the same time.


| Command              | Action                                  |
| ------------------------- | ------------------------------------- |
| RISTRETTO + LUNGO 6 sec -> Flash | After filling water tank |
| LUNGO + ESPRESSO 3 sec | OFF |
| SLIDER | ON |
| OFF, then ESPRESSO + LUNGO 5 sec -> Blink | Factory reset |
| RISTRETTO + ESPRESSO, then remove plug | Unpair |



