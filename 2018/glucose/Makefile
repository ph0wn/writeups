all:

build:
	docker build -t ph0wn18/mqtt:latest .

stop:
	docker stop ph0wn18-mqtt
	docker rm ph0wn18-mqtt

run:
	docker run -d -p 1883:1883 --name ph0wn18-mqtt ph0wn18/mqtt:latest

connect:
	docker exec -it  ph0wn18-mqtt /bin/sh
