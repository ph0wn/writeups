#!/bin/bash

for i in {1..10}
do
    echo "Client #$i"
    printf "help" | nc 127.0.0.1 7777 &
done

echo "Sleeping"
sleep 60
    
