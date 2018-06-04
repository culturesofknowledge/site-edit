#!/usr/bin/env bash

# Build, run, in background.
docker-compose up -d --build --remove-orphans
sleep 5
docker-compose ps