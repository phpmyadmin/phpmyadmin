#!/bin/sh
#
# vim: expandtab sw=4 ts=4 sts=4:
#

# This script will sync JS node_modules files with existing files in vendor folder.
# Warning: this will not add any more files, it uses existing files in vendor folder.

ROOT_DIR="$(realpath $(dirname $0)/../)"
echo "Using root dir: $ROOT_DIR"

echo 'Updating codemirror'
cd ./js/vendor/codemirror
find * -type f -print -exec cp ../../../node_modules/codemirror/{} {} \;
cd ${ROOT_DIR}

echo 'Updating jquery'
cp ./node_modules/jquery/dist/jquery.min.js ./js/vendor/jquery/jquery.min.js
cp ./node_modules/jquery/dist/jquery.min.map ./js/vendor/jquery/jquery.min.map
cp ./node_modules/jquery/LICENSE.txt ./js/vendor/jquery/MIT-LICENSE.txt
echo 'Updating jquery-migrate'
cp ./node_modules/jquery-migrate/dist/jquery-migrate.js ./js/vendor/jquery/jquery-migrate.js
echo 'Updating bootstrap'
cp ./node_modules/bootstrap/dist/js/bootstrap.bundle.min.js ./js/vendor/bootstrap/bootstrap.bundle.min.js
cp ./node_modules/bootstrap/dist/js/bootstrap.bundle.min.js.map ./js/vendor/bootstrap/bootstrap.bundle.min.js.map

echo 'Done.'
