#!/bin/bash

set -e

if [ $# -lt 1 ]; then
	echo "usage: $0 <svn dir>"
	exit 1
fi

dir="$( cd "$(dirname "$( dirname "${BASH_SOURCE[0]}" )" )" >/dev/null 2>&1 && pwd )"
version="$(cat "$dir/readme.txt" | grep "Stable tag: " | cut -c 13-)"
svn="$1"

if [ -d "$svn/tags/$version" ] ; then
  echo "Error: $svn/tags/$version already exists"
  exit 1
fi

echo "Running grunt..."
npm run grunt

echo "Building assets..."
cd "$dir"
npm run build

echo "Creating $svn/tags/$version..."
mkdir "$svn/tags/$version"
rsync --recursive \
      --verbose \
      --include="vendor/autoload.php" \
      --exclude-from=".distignore" \
      "$dir/" \
      "$svn/tags/$version/"

echo "Copying to $svn/trunk..."
rsync --recursive \
      --verbose \
      "$svn/tags/$version/" \
      "$svn/trunk/"

echo "Done"
