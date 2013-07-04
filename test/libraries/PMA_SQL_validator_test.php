<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for correctness of SQL validator
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
define('PMA_MYSQL_STR_VERSION', "PMA_MYSQL_STR_VERSION");
global $cfg;

require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Message.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/sqlvalidator.class.php';
require_once 'libraries/sqlvalidator.lib.php';

class PMA_SQLValidator_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    public function setUp()
    {
        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('utf-8');
        }
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $GLOBALS['pmaThemeImage'] = 'theme/';
        $GLOBALS['cfg']['SQLValidator']['username'] = "PMA_username";
        $GLOBALS['cfg']['SQLValidator']['password'] = "PMA_password";
    }
    
    public function testPMA_validateSQL()
    {
    	$sql = "select * from PMA_test";
        $GLOBALS['cfg']['SQLValidator']['use'] = false;
        $this->assertEquals(
        	'',
        	PMA_validateSQL($sql)
        );

        $GLOBALS['cfg']['SQLValidator']['use'] = true;
        $GLOBALS['sqlvalidator_error'] = true;
        $this->assertContains(
        	'The SQL validator could not be initialized.',
        	PMA_validateSQL($sql)
        );
    }
}
?>
