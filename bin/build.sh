#!/bin/bash

set -e

echo "Building and then starting containers..."
echo
docker-compose build
docker-compose up -d
echo

echo "Running composer install..."
echo
docker-compose exec web composer --working-dir="/var/www/html/wp-content/plugins/wp-shlink" install
docker-compose exec web /var/www/html/wp-content/plugins/wp-shlink/bin/drop-database.sh
docker-compose exec web /var/www/html/wp-content/plugins/wp-shlink/bin/install-wp-tests.sh wordpress wordpress wordpress db
echo

echo "Installing node.js dependencies and building front-end assets..."
echo
npm install
npm run build
echo

echo "Shutting off containers..."
echo
docker-compose stop
echo
echo "All done!"
