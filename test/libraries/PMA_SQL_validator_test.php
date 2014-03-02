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
define('PMA_MYSQL_STR_VERSION', "5.00.15");
//it will be used before setup on libraries/sqlvalidator.lib.php
global $cfg;
$cfg['SQLValidator']['use'] = false;

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

    /**
     * Tests for PMA_validateSQL failed due to No Configure
     *
     * @return void
     */
    public function testPMA_validateSQL_NoConfigure()
    {
        $sql = "select * from PMA_test";

        //$cfg['SQLValidator']['use'] = false
        $this->assertEquals(
            '',
            PMA_validateSQL($sql)
        );

        //$cfg['SQLValidator']['use'] = true
        $GLOBALS['cfg']['SQLValidator']['use'] = true;

        //the sql validatior is not loaded
        $GLOBALS['sqlvalidator_error'] = true;
        $this->assertContains(
            'The SQL validator could not be initialized.',
            PMA_validateSQL($sql)
        );
    }

    /**
     * Tests for PMA_validateSQL SOAP
     *
     * @return void
     */
    public function testPMA_validateSQL_SOAP()
    {
        $sql_pass = "select * from PMA_test";
        $sql_fail = "select * PMA_test";

        //the sql validatior is loaded correctly
        //follow need SOAP
        $GLOBALS['cfg']['SQLValidator']['use'] = true;
        $GLOBALS['sqlvalidator_soap'] = 'PEAR';
        $GLOBALS['sqlvalidator_error'] = false;

        //validate that the result is the same as SOAP_Client return
        //SOAP_Client is mocked with simple logic
        $this->assertTrue(
            PMA_validateSQL($sql_pass)
        );
        $this->assertFalse(
            PMA_validateSQL($sql_fail)
        );
    }
}

//Mock the SOAP_Client
class SOAP_Client
{
    public function call($name, $arguments)
    {
        return $this->{$name}($arguments);
    }
    public function openSession($args)
    {
        $session = new Session;
        $session->target = "http://sqlvalidator.mimer.com/v1/services";
        $session->username = $args["a_userName"];
        $session->password = $args["a_password"];
        $session->calling_program = $args["a_callingProgram"];
        $session->sessionId = "sessionId";
        $session->sessionKey = "sessionKey";
        return $session;
    }
    public function validateSQL($args)
    {
        $session = new Session;
        $sql = $args["a_SQL"];
        //simple logic of sql validate
        $pos = strstr($sql, "from");
        if (!$pos) {
            $session->data = false;
        } else {
            $session->data = true;
        }
        return $session;
    }
}

//Mock return Session class
class Session
{
    var $target          = null;
    var $username        = null;
    var $password        = null;
    var $calling_program = null;
    var $sessionId       = null;
    var $sessionKey      = null;
    var $data            = null;
}

?>
