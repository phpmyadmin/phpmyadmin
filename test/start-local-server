#!/bin/sh

set -e
set -x

if [ -z  "$SELENIUM" ] ; then
    exit 0
fi

php --server 127.0.0.1:8000 > php.log &
~/browserstack/BrowserStackLocal -localIdentifier "travis-$TRAVIS_JOB_NUMBER" -onlyAutomate "$TESTSUITE_BROWSERSTACK_KEY" 127.0.0.1,8000,0 & 
