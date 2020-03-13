#!/bin/sh
#
# vim: expandtab sw=4 ts=4 sts=4:
#

# This script will sync JS node_modules files with existing files in vendor folder.
# Warning: this will not add any more files, it uses existing files in vendor folder.

ROOT_DIR="$(dirname $0)/../"
echo "Using root dir: $ROOT_DIR"

cd ./js/vendor/codemirror
find * -type f -print -exec cp ../../../node_modules/codemirror/{} {} \;
