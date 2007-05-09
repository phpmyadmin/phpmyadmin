<?php
/* vim: expandtab sw=4 ts=4 sts=4: */
/**
 * runs all defined tests
 *
 * @version $Id$
 * @package phpMyAdmin-test
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
//require_once 'PHPUnit/Util/TestDox/ResultPrinter.php.';
require_once './test/FailTest.php';
require_once './test/PMA_get_real_size_test.php';
require_once './test/PMA_sanitize_test.php';
require_once './test/PMA_pow_test.php';
require_once './test/Environment_test.php';
require_once './test/PMA_escapeJsString_test.php';
require_once './test/PMA_isValid_test.php';
require_once './test/PMA_transformation_getOptions_test.php';
require_once './test/PMA_STR_sub_test.php';

class AllTests
{
    public static function main()
    {
        $parameters = array();
        //$parameters['testdoxHTMLFile'] = true;
        //$parameters['printer'] = PHPUnit_Util_TestDox_ResultPrinter::factory('HTML');
        //$parameters['printer'] = PHPUnit_Util_TestDox_ResultPrinter::factory('Text');
        PHPUnit_TextUI_TestRunner::run(self::suite(), $parameters);
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('phpMyAdmin');

        //$suite->addTestSuite('FailTest');
        $suite->addTestSuite('Environment_test');
        $suite->addTestSuite('PMA_get_real_size_test');
        $suite->addTestSuite('PMA_sanitize_test');
        $suite->addTestSuite('PMA_pow_test');
        $suite->addTestSuite('PMA_escapeJsString_test');
        $suite->addTestSuite('PMA_isValid_test');
        $suite->addTestSuite('PMA_transformation_getOptions_test');
        $suite->addTestSuite('PMA_STR_sub_test');
        return $suite;
    }
}

if (PMA_MAIN_METHOD == 'AllTests::main') {
    echo '<pre>';
    AllTests::main();
    echo '</pre>';
}
?>