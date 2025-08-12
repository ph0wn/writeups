Log on the robot. This is a Linux system.

# Basics
-You can first list the connected motors:
$ for f in /sys/class/tacho-motor/*; do echo -n "$f: "; cat $f/address; done

- Then, it is wise to define variables for motors (adapt the value depending on previous command):
$ export MA=/sys/class/tacho-motor/motor0
$ export MB=/sys/class/tacho-motor/motor1
$ export MC=/sys/class/tacho-motor/motor2

The idea is to first raise the arm with motor B, then rotate to the total left until the opened part of the door has been reached, then lower the arm, then open the claw, then go on the right.

# How to use motors

Example with motor MC
- To get all possible commands:
$ cat $MA/commands

- Setting speed value (use a negative value for the other direction)
echo 100 > $MA/speed_sp

- Setting time of running
echo run-timed > $MA/command
