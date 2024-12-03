(independent from stage 2)

We would like to know whether transition labels are reachable or not in the reachability graph. For each transition label which is accessible from the initial state, the flag contains a T (for true) or a F (for false) when not reachable.

For instance, let us consider the following transitionLabels: a, u, p, d, e.

There exists at least one execution paths leading to transition labels a, d and e, but there is no paths leading to labels u or p.
So, the flag would be: `ph0wn{TFFTT}`

To identify the flag of this challenge, we expect you to compute reachabilities for the following transition labels (one per line):  

SpeedSensor/currentSpeed=currentSpeed+speedIncrement
ObstacleGenerator/nbOfObstacles=2
!readSpeed?readSpeed(205)
!infoDetectedObstacles?infoDetectedObstacles(1)
!goObstacle?goObstacle()
!readSpeed?readSpeed(149)
!emergencyBrakingON?emergencyBrakingON(1)
!obstacleDetected?obstacleDetected(2)
SpeedSensor/currentSpeed=currentSpeed-speedIncrement
!goSpeed?goSpeed()
Maincontroller/Pic0L0vesCh0c0late
MainController/braking=2
SpeedSensor/currentSpeed=currentSpeed-speedIncrement
!readSpeed?readSpeed(30)
!GoodLuckAlmostThere?GoodLuckAlmostThere(12)
!readSpeed?readSpeed(305)
ObstacleGenerator/nbOfObstacles=1


Graph file to be used: `rg.aut.gz` to be downloaded from http://chal.ph0wn.org:9000/
