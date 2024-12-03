#!/bin/bash


if [ ! -x "$(command -v docker)" ]; then
    echo "[X] Docker is not installed. Please install docker and try again."
    exit 1
else
    echo "[*] Docker is installed."
fi


if [ $(groups | grep -c docker) -eq 0 ]; then
    echo "[X] User does not belong to docker group. Please create a user and add the user to docker group and try again.\n"
    exit 1
else
    echo "[*] User belongs to docker group."
fi


if [ $(systemctl is-active docker) != "active" ]; then
    echo "[X] Docker service is not running. Please start docker service and try again."
    exit 1
else
    echo "[*] Docker service is running."
fi

echo "[*] Building docker image..."
docker compose up --build -d

if [ $? -eq 0 ]; then
    echo "[*] Docker image is up."
else
    echo "[X] Docker image build failed."
    exit 1
fi
