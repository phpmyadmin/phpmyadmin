#!/bin/sh
#
# vim: expandtab sw=4 ts=4 sts=4:
#

# Do not run as CGI
if [ -n "$GATEWAY_INTERFACE" ] ; then
    echo 'Can not invoke as CGI!'
    exit 1
fi

if [ -f package.json ] ; then
    echo "Running Yarn Install"
    yarn install

    if  [ -d node_modules ] ; then
        echo "Creating production build of js files"
        yarn prod:build
    fi
fi

#Performing cleanup
#Removing yarn files
if [ -f yarn.lock ] ; then
    rm -f yarn.lock
fi

if [ -f yarn-error.log ] ; then
    rm -f yarn-error.log
fi
#Removing node_modules from the directory
rm -rf node_modules
#Removing the babel transcompiled code
rm -rf js/lib
#Removing JavaScript source code
rm -rf js/src
#Removing babel files as they are not required in production
rm -f .babelrc
rm -f webpack.config.babel.js
rm -f .jshintrc

echo "Finished building js files"
