<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium parent class for TestCases
 *
 * @package phpMyAdmin-test
 */

// Optionally add the php-client-driver to your include path
set_include_path(get_include_path() . PATH_SEPARATOR . '/opt/selenium-remote-control-1.0.1/selenium-php-client-driver-1.0.1/PEAR/');

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/Extensions/SeleniumTestCase.php';
require_once 'Testing/Selenium.php';

// Include the main phpMyAdmin user config
// currently only $cfg['Test'] is used
require_once '../config.inc.php';



class PmaSeleniumTestCase extends PHPUnit_Extensions_SeleniumTestCase
{
    protected $selenium;
    protected $cfg;

    // TODO: find a way to get this from config.inc.php???  
    // 	PHPUnit also has a way to use XML configuration... maybe we should use that
    public static $browsers = array(
        array(

            'name'    => 'Firefox on Windows XP',
            'browser' => '*firefox',
            'host'    => 'my.windowsxp.box',
            'port'    => 4444,
            'timeout' => 30000,
        ),

        array(
            'name'    => 'Internet Explorer on Windows XP',
            'browser' => '*iexplore',
            'host'    => 'my.windowsxp.box',
            'port'    => 4444,
            'timeout' => 30000,
        ),

    );

    public function setUp()
    {
        global $cfg;
        $this->cfg =& $cfg;
        //PHPUnit_Extensions_SeleniumTestCase::$browsers = $this->cfg['Test']['broswers'];

        // Check if the test configuration is available
        if ( empty($cfg['Test']['pma_host'])
            || empty($cfg['Test']['pma_url'])
                //|| empty($cfg['Test']['browsers'])
                ) {
                    $this->fail("Missing Selenium configuration in config.inc.php"); // TODO add doc ref?
                }

        $this->setBrowserUrl($cfg['Test']['pma_host'] . $cfg['Test']['pma_url']);

        $this->start();
    }

    public function tearDown()
    {
        $this->stop();
    }

    /**
     *	perform a login
     */ 
    public function doLogin()
    {
        $this->open($this->cfg['Test']['pma_url']);
        // Somehow selenium does not like the language selection on the cookie login page, forced English in the config for now.
        //$this->select("lang", "label=English");

        $this->waitForPageToLoad("30000");
        $this->type("input_username", $this->cfg['Test']['testuser']['username']);
        $this->type("input_password", $this->cfg['Test']['testuser']['password']);
        $this->click("input_go");
        $this->waitForPageToLoad("30001");
    }

    /*
     * 	Just a dummy to show some example statements
     *
     public function mockTest()
     {
         // Slow down the testing speed, ideal for debugging
         //$this->setSpeed(4000);
}
     */
}

?>
