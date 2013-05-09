<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium Helper class for selenium test cases
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
require_once 'TestConfig.php';

/**
 * Selenium Helper Class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class Helper
{
    /**
     * SeleniumTestSuite instance
     *
     * @var object
     */
    public static $selenium;

    /**
     * TestConfig instance
     *
     * @var object TestConfig
     */
    public static $config;

    /**
     * constructor
     *
     */
    function __construct()
    {
        self::$config = new TestConfig();
    }

    public static function isLoggedIn($selenium)
    {
        return $selenium->isElementPresent('//*[@id="serverinfo"]/a[1]');
    }

    public static function logOutIfLoggedIn($selenium)
    {
        if (self::isLoggedIn($selenium)) {
            $selenium->clickAndWait("css=img.icon.ic_s_loggoff");
        }
    }

    public static function getBrowserString()
    {
        $browserString = self::$config->getCurrentBrowser();
        return $browserString;
    }
}
?>
