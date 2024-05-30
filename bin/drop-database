#!/bin/bash

set -e

if [ $(mysql --user="wordpress" --password="wordpress" --host="db" --execute='show databases;' | grep ^wordpress$) ]
then
	echo
	echo "Dropping database..."
	echo
	mysqladmin drop wordpress -f --host="db" --user="wordpress" --password="wordpress"
fi
