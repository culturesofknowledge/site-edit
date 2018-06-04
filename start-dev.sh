#!/usr/bin/env bash

# Start (and build if necessary)
docker-compose --file docker-compose-dev.yaml up -d --build
sleep 5
docker-compose --file docker-compose-dev.yaml logs --tail="10"

# Output status
echo
echo "Container status:"
docker-compose --file docker-compose-dev.yaml ps

# Tail all the output (Ctrl-c to stop)
echo
echo "Tailing logs:"
docker-compose --file docker-compose-dev.yaml logs -f --tail="0"