for entry in ./firmware/external_modules/*.py
do
  ampy -p /dev/ttyUSB0 put $entry
done
ampy -p /dev/ttyUSB0 put ./TheSignal_challenge/char_probability.txt
