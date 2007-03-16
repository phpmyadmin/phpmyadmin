<?php
/* vim: expandtab sw=4 ts=4 sts=4: */
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
require_once './test/PMA_sanitize_test.php';
require_once './test/PMA_pow_test.php';

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
        $suite->addTestSuite('PMA_sanitize_test');
        $suite->addTestSuite('PMA_pow_test');
        return $suite;
    }
}

if (PMA_MAIN_METHOD == 'AllTests::main') {
    AllTests::main();
}
?>