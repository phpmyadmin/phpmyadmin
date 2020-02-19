PhpMyAdmin test suite
=====================

This directory is protected from web visitors by a .htaccess file.

For more information on allowing http access to this directory see:
https://httpd.apache.org/docs/current/mod/mod_authz_host.html#allow

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

TESTSUITE_BROWSERSTACK_USER
    BrowserStack username.
TESTSUITE_BROWSERSTACK_KEY
    BrowserStack access key.

For example you can use following setup in ``phpunit.xml``::

    <php>
        <env name="TESTSUITE_SERVER" value="localhost"/>
        <env name="TESTSUITE_USER" value="root"/>
        <env name="TESTSUITE_PASSWORD" value="root"/>
        <env name="TESTSUITE_DATABASE" value="test"/>
        <env name="TESTSUITE_URL" value="http://localhost/phpmyadmin/" />
        <env name="TESTSUITE_SELENIUM_HOST" value="127.0.0.1" />
        <env name="TESTSUITE_SELENIUM_PORT" value="4444" />
    </php>

Using BrowserStack
------------------

We're using BrowserStack to run our tests on the Travis CI. If you are a team
member, you can be granted access to the team account, but you can register own
account there as well.

To run tests locally, you need to install BrowserStack tool to enable local
testing, see their website for instructions:

https://www.browserstack.com/local-testing#command-line

Following instructions use PHP's built in server for the testing::

    # Export BrowserStack credentials in the environment:
    export TESTSUITE_BROWSERSTACK_USER=your_username
    export TESTSUITE_BROWSERSTACK_KEY=your_key

    # Port where tests will be running
    export TESTSUITE_PORT=9000
    export TESTSUITE_URL=http://127.0.0.1:$TESTSUITE_PORT/

    # Start PHP built in server
    php --server 127.0.0.1:$TESTSUITE_PORT > php.log &

    # Start BrowserStack Local client to forward the traffic
    ~/browserstack/BrowserStackLocal -localIdentifier Manual "$TESTSUITE_BROWSERSTACK_KEY" 127.0.0.1,$TESTSUITE_PORT,0 & 
