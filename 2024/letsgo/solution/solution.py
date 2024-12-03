#!/usr/bin/env python3
from ev3dev2.sensor.lego import ColorSensor
from ev3dev2.motor import MoveTank, OUTPUT_B, OUTPUT_D, SpeedPercent
import time
from ev3dev2.sensor.lego import UltrasonicSensor
from ev3dev2.sensor.lego import GyroSensor

color_sensor = ColorSensor()
tank_drive = MoveTank(OUTPUT_B, OUTPUT_D)
ultrasonic_sensor = UltrasonicSensor()
gyro = GyroSensor()
gyro.reset()
th=2

print("Ready")

while True:
    ambient_light = color_sensor.ambient_light_intensity
    if ambient_light > 25:
        #It means the red light is on
        break

while True:
    ambient_light = color_sensor.ambient_light_intensity
    if ambient_light < 10:
        #It means the red light is OFF
        break

tank_drive.on(SpeedPercent(100), SpeedPercent(100))

while True:

    angle = gyro.angle

    if abs(angle)>th:
        print(angle)
        if angle>0:
            tank_drive.on(SpeedPercent(75), SpeedPercent(100))
        else:
            tank_drive.on(SpeedPercent(100), SpeedPercent(75))
    else:
        tank_drive.on(SpeedPercent(100), SpeedPercent(100))

    distance_cm = ultrasonic_sensor.distance_centimeters

    if distance_cm < 20:
        tank_drive.off()
        break



