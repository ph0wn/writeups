

build:
	docker build -t ph0wn18/opcua-challenge .

run:
	docker run -d -p 6666:22 -p "6100-6300":"6100-6300" --name savethefactory ph0wn18/opcua-challenge

stop:
	docker stop savethefactory
	docker rm savethefactory

connect:
	ssh -p 6666 root@35.233.31.82


