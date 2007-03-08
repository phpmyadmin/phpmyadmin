<?php
/**
 * runs all defined tests
 *
 */

/**
 *
 */
if (! defined('PMA_MAIN_METHOD')) {
    define('PMA_MAIN_METHOD', 'AllTests::main');
    chdir('..');
}

/**
 *
 */
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once './test/FailTest.php';
require_once './test/PMA_get_real_size_test.php';

class AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('phpMyAdmin');

        //$suite->addTestSuite('FailTest');
        $suite->addTestSuite('PMA_get_real_size_test');

        return $suite;
    }
}

if (PMA_MAIN_METHOD == 'AllTests::main') {
    AllTests::main();
}
?>