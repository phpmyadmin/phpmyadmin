<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_fatalError() from libraries/core.lib.php
 *
 * PMA_fatalError() displays the given error message on phpMyAdmin error page in foreign language
 * and ends script execution and closes session
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/select_lang.lib.php';
require_once 'libraries/core.lib.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/Config.class.php';

class PMA_fatalError_test extends PHPUnit_Extensions_OutputTestCase
{
    public function testFatalErrorMessage()
    {
        $this->expectOutputRegex("/FatalError!/");
        PMA_fatalError("FatalError!");
    }

    public function testFatalErrorMessageWithArgs()
    {
        $message = "Fatal error #%d in file %s.";
        $params = array(1, 'error_file.php');

        $this->expectOutputRegex("/Fatal error #1 in file error_file.php./", "Not EQ");
        PMA_fatalError($message, $params);

        $message = "Fatal error in file %s.";
        $params = 'error_file.php';

        $this->expectOutputRegex("/Fatal error in file error_file.php./");
        PMA_fatalError($message, $params);
    }

}