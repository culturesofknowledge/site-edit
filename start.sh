#!/usr/bin/env bash
docker-compose up -d --build --remove-orphans
sleep 5
docker-compose ps