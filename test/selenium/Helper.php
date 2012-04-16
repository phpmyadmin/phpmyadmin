<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium Helper Class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
require_once 'TestConfig.php';

class Helper
{
    public static $selenium;
    public static $config;

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
            $selenium->selectFrame("frame_navigation");
            $selenium->clickAndWait("css=img.icon.ic_b_home");
        }
    }

    public static function getBrowserString()
    {
        $browserString = self::$config->getCurrentBrowser();
        return $browserString;
    }
}
?>
