#!/bin/bash

cd /home/alberry/Ph0wn2018

{
while [ 1 ]; do
	socat TCP4-LISTEN:12345,fork,reuseaddr,rcvbuf=1,nodelay SYSTEM:"timeout 600 ./alberry",pty,ctty,rawer,echo=0
done
} >> /home/alberry/alberry.log  2>&1
