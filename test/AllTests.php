<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * runs all defined tests
 *
 * @package phpMyAdmin-test
 */

/**
 *
 */
if (! defined('PMA_MAIN_METHOD')) {
    define('PMA_MAIN_METHOD', 'AllTests::main');
    chdir('..');
}

// required to not die() in some libraries
define('PHPMYADMIN', true);

// just add $_SESSION array once, so no need to test for existance evrywhere to get rid of NOtices about this
if (empty($_SESSION)) {
    $_SESSION = array();
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
require_once './test/PMA_generateCommonUrl_test.php';
require_once './test/PMA_blowfish_test.php';
require_once './test/PMA_escapeMySqlWildcards_test.php';
require_once './test/PMA_showHint_test.php';
require_once './test/PMA_formatNumberByteDown_test.php';
require_once './test/PMA_localisedDateTimespan_test.php';
require_once './test/PMA_cache_test.php';
require_once './test/PMA_quoting_slashing_test.php';
require_once './test/PMA_stringOperations_test.php';
require_once './test/PMA_printableBitValue_test.php';
require_once './test/PMA_foreignKeySupported_test.php';
require_once './test/PMA_headerLocation_test.php';
require_once './test/PMA_Message_test.php';
require_once './test/PMA_whichCrlf_test.php';

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
        $suite->addTestSuite('PMA_generate_common_url_test');
        $suite->addTestSuite('PMA_blowfish_test');
        $suite->addTestSuite('PMA_escapeMySqlWildcards_test');
        $suite->addTestSuite('PMA_showHint_test');
        $suite->addTestSuite('PMA_formatNumberByteDown_test');
        $suite->addTestSuite('PMA_localisedDateTimespan_test');
        $suite->addTestSuite('PMA_cache_test');
        $suite->addTestSuite('PMA_quoting_slashing_test');
        $suite->addTestSuite('PMA_stringOperations_test');
        $suite->addTestSuite('PMA_printableBitValue_test');
        $suite->addTestSuite('PMA_foreignKeySupported_test');
        $suite->addTestSuite('PMA_headerLocation_test');
        $suite->addTestSuite('PMA_Message_test');
        $suite->addTestSuite('PMA_whichCrlf_test');
        return $suite;
    }
}
?>
