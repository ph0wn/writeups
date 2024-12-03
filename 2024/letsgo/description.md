Pico needs your help to set the best time of the day with a lightning-fast start and an amazing drive on track.

Robot:

- The challenge uses EV3 robots (https://www.ev3dev.org/)
- Each robot has at disposal 2 motors for motion, one luminosity sensor, one gyroscope, and one distance sensor in the front part.
- The archive below contains a few templates with prototypes to use all the above components. 
- All the robots are built in the same way and by using the same components, batteries, sensors, and motors.
- There is one robot at disposal for testing at the org desk and two racing ones.
- You will be given SSH access to a test robot to upload and run your code.
- You will be asked to send the code you want to use as part of the challenge to one of the organizers who will then run it for you on a racing robot on the track.

Goal:

- Once the code is uploaded, the organizer gave green light, and team is ready, one member of the team will place the robot in the starting position before the white line and with the luminosity sensor against the semaphore.
- One team member will start the departure procedure by pressing a button on the track.
- After a random delay from pressing the button (i.e., max 10 seconds), the semaphore will turn red and then will turn off for 2 seconds: in this 2-second time frame, the robot must start the race.
- Run as fast as you can between the start sensor and the finish sensor to get the fastest time.
- After the finish line, the robot must stop between the finish line and the barriers, without interrupting the finish sensor - i.e., it must complete the race.

Challenge rules:

- Jump starts (moving off your grid position before the five red lights on the start line go out) are (of course) not allowed: after pressing the starting button, the robot must start in the 2-second window when the lights are off after they lighted up in red.
- The robot must cross the two lines (start and finish) and a time must be recorded and appear on the screen.
- If the robot fails at stopping at the end and touches any barrier, sensor, light the challenge is not valid.

Score:

- If the goal is achieved and all the rules above are respected, you will receive the flag and get the 300 points.
- The challenge will be closed 30 minutes before the end of the CTF to assign the points to the best 3 teams, who will receive **extra 200, 150 and 100 points** for 1st, 2nd and 3rd classified based on the timing appearing on the screen.
- An updated scoreboard is available -> https://bit.ly/418xxOT
- If two teams set the best time, the one who registered it earlier is picked as best time.
- You'll be given at most 10 attempts on the track, after which any registered time will be incrementally decreased by 1 tenth of second (e.g., 11th attempt -> -1 tenth, 12th attempt -> -2 tenths, etc.)

Notes:

- Any intentional interference with the robot OS (e.g. tampering) will cause **team disqualification from the CTF**
