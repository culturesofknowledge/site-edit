#!/usr/bin/env bash
docker-compose --file docker-compose-dev.yaml up -d --build
sleep 5
docker-compose --file docker-compose-dev.yaml logs --tail="10"

echo
echo "Container status:"
docker-compose --file docker-compose-dev.yaml ps

echo
echo "Tailing logs:"
docker-compose --file docker-compose-dev.yaml logs -f --tail="0"