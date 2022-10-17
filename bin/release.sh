#!/bin/bash

set -e

if [ -d /tmp/smol-links ] ; then
  echo "Error: /tmp/smol-links already exists"
  exit 1
fi

dir="$( cd "$(dirname "$( dirname "${BASH_SOURCE[0]}" )" )" >/dev/null 2>&1 && pwd )"

echo "Building assets..."
cd "$dir"
npm run build

echo "Creating /tmp/smol-links..."
mkdir /tmp/smol-links
rsync --recursive \
      --verbose \
      --include="vendor/autoload.php" \
      --exclude-from=".distignore" \
      "$dir/" \
      /tmp/smol-links/

echo "Zipping release..."
cd /tmp
zip -r smol-links.zip smol-links

echo "Cleaning up /tmp/smol-links..."
rm -rf /tmp/smol-links

echo "Ready: /tmp/smol-links.zip"
