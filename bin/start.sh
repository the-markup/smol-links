#!/bin/bash

set -e

stop_containers() {
  echo "Shutting off containers..."
  echo
  docker-compose stop
  echo
  echo "All done!"
}

trap stop_containers SIGINT

echo "Starting containers..."
echo
docker-compose up -d
echo

echo "Running composer install..."
echo
docker-compose exec web composer --working-dir="/var/www/html/wp-content/plugins/wp-shlink" install
echo

echo "Running npm install..."
echo
npm install
echo

echo "Starting dev server..."
echo
npm run start
