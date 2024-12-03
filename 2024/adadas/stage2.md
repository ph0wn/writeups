(independent from stage 1)

We would like to know whether transition labels are live or not in the reachability graph. A live label means that whatever the execution path, the path goes through this label. Said differently, the liveness of a label is satisfied if and only if the reachability of a label is satisfied in all possible execution paths.

For instance, let us consider the following transitionLabels of the example graph: c and d.

If we consider the transitions c and d, there exists at least one execution paths never going through c: adeadeadeade... infinitely. For transition d, all execution paths necessarily execute d.

In such a case, the flag would be: `ph0wn{FT}`

To identify the flag of this challenge, we expect you to compute the liveness of the following transition labels. Also, important note: a tag t is considered as met in a transition if the transition label contains "t". For instance, "t" is considered met in a transition with label "atr" or "t" or "tt" or "ta", but not in "ww" nor "pic0".

  
Transition labels (one per line):

!emergencyBrakingON?emergencyBrakingON()
!braking?braking
!braking?braking(0)
!braking?braking(1)
!goObstacle?goObstacle
!goObstacle?goObstacle(1)
!obstacleDetected?obstacleDetected
SpeedSensor/currentSpeed=currentSpeed+speedIncrement
SpeedSensor/currentSpeed=minSpeed
MainController/Pic0LovesSp0rtCars
ObstacleGenerator/nbOfObstacles=0
ObstacleGenerator/nbOfObstacles
!infoDetectedObstacles?infoDetectedObstacles
!infoDetectedObstacles?infoDetectedObstacles(1)
!readSpeed?readSpeed(185)
!readSpeed?readSpeed(1)


Graph file to be used: `rg.aut.gz` (same as stage 1)
