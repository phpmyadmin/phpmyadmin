<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium parent class for TestCases
 *
 * @package phpMyAdmin-test
 * @group Selenium
 */

// Optionally add the php-client-driver to your include path
//set_include_path(get_include_path() . PATH_SEPARATOR . '/opt/selenium-remote-control-1.0.1/selenium-php-client-driver-1.0.1/PEAR/');

// Include the main phpMyAdmin user config
// currently only $cfg['Test'] is used
require_once 'config.sample.inc.php';



class PmaSeleniumTestCase extends PHPUnit_Extensions_SeleniumTestCase
{
    protected $selenium;
    protected $cfg;

    protected $captureScreenshotOnFailure = TRUE;
    protected $screenshotPath = '/var/www/screenshots';
    protected $screenshotUrl = 'http://localhost/screenshots';

    public function setUp()
    {
        global $cfg;
        $this->cfg =& $cfg;
        //PHPUnit_Extensions_SeleniumTestCase::$browsers = $this->cfg['Test']['broswers'];

        $this->setBrowserUrl(TESTSUITE_PHPMYADMIN_HOST . TESTSUITE_PHPMYADMIN_URL);

        $this->start();
    }

    public function tearDown()
    {
        $this->stop();
    }

    /**
     * perform a login
     */
    public function doLogin()
    {
        $this->open(TESTSUITE_PHPMYADMIN_URL);
        // Somehow selenium does not like the language selection on the cookie login page, forced English in the config for now.
        //$this->select("lang", "label=English");

        $this->waitForPageToLoad("30000");
        $this->type("input_username", TESTSUITE_USER);
        $this->type("input_password", TESTSUITE_PASSWORD);
        $this->click("input_go");
        $this->waitForPageToLoad("30001");
    }

    /*
     * Just a dummy to show some example statements
     *
     public function mockTest()
     {
         // Slow down the testing speed, ideal for debugging
         //$this->setSpeed(4000);
}
     */
}

?>
