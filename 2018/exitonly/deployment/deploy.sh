#!/bin/bash

set -x

# stop if already existing
docker container stop $(docker container ls | grep exitonly | cut -d" " -f1)

docker build . -t exitonly

CONTID=$(docker run -d --rm exitonly)

IP=$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' ${CONTID})

echo "CONTAINER ID: ${CONTID}"
echo "TARGET IP: ${IP}"
