<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * runs all defined Selenium tests
 *
 * @package PhpMyAdmin-test
 */


/**
 *
 */
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once dirname(__FILE__).'/selenium/PmaSeleniumTestCase.php';
require_once dirname(__FILE__).'/selenium/PmaSeleniumLoginTest.php';
require_once dirname(__FILE__).'/selenium/PmaSeleniumXssTest.php';
require_once dirname(__FILE__).'/selenium/PmaSeleniumPrivilegesTest.php';

class AllSeleniumTests
{
    public static function main()
    {
        $parameters = array();
        PHPUnit_TextUI_TestRunner::run(self::suite(), $parameters);
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('phpMyAdmin');

        $suite->addTestSuite('PmaSeleniumLoginTest');
        //$suite->addTestSuite('PmaSeleniumXssTest');
        //$suite->addTestSuite('PmaSeleniumPrivilegesTest');
        return $suite;
    }
}
?>
