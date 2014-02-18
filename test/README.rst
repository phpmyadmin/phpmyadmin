PhpMyAdmin test suite
=====================

This directory is protected from web visitors by a .htaccess file.

For more information on allowing http access to this directory see: 
http://httpd.apache.org/docs/current/mod/mod_authz_host.html#allow

Please visit the wiki for more information on unit testing:
https://wiki.phpmyadmin.net/pma/UnitTesting

Selenium tests
--------------

To be able to run Selenium tests, you need to have webserver, database
and Selenium running. Following environment variables configure where
testsuite connects:

TESTSUITE_SERVER
    Database server to use.
TESTSUITE_USER
    Username for connecting to database.
TESTSUITE_PASSWORD
    Password for connecting to database.
TESTSUITE_DATABASE
    Database to use for testing.
TESTSUITE_URL
    URL where tested phpMyAdmin is available.

Additionally you need to configure link to Selenium and browsers. You
can either setup Selenium locally or use BrowserStack automated testing.

For local setup, define following:

TESTSUITE_SELENIUM_HOST
    Host where Selenium is running.
TESTSUITE_SELENIUM_PORT
    Port where to connect.
TESTSUITE_SELENIUM_BROWSER
    Browser to use for testing inside Selenium.

With BrowserStack, set following:

TESTSUITE_BROWSERSTACK_UNAME
    BrowserStack username.
TESTSUITE_BROWSERSTACK_KEY
    BrowserStack access key.
