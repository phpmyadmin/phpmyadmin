<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_fatalError() from libraries/core.lib.php
 *
 * PMA_fatalError() displays the given error message on phpMyAdmin error page in
 * foreign language
 * and ends script execution and closes session
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/vendor_config.php';
require_once 'libraries/core.lib.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/select_lang.lib.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Table.class.php';
require_once 'libraries/php-gettext/gettext.inc';

/**
 * Test for PMA_fatalError() from libraries/core.lib.php
 *
 * PMA_fatalError() displays the given error message on phpMyAdmin error page in
 * foreign language
 * and ends script execution and closes session
 *
 * @package PhpMyAdmin-test
 */
class PMA_FatalError_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Set up
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['PMA_Config'] = new PMA_Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg']['Server'] = array(
            'host' => 'host',
            'verbose' => 'verbose',
        );
        $GLOBALS['cfg']['OBGzip'] = false;
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $GLOBALS['pmaThemeImage'] = 'theme/';
        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
    }

    /**
     * Test for PMA_fatalError
     *
     * @return void
     */
    public function testFatalErrorMessage()
    {
        $this->expectOutputRegex("/FatalError!/");
        PMA_fatalError("FatalError!");
    }

    /**
     * Test for PMA_fatalError
     *
     * @return void
     */
    public function testFatalErrorMessageWithArgs()
    {
        $message = "Fatal error #%d in file %s.";
        $params = array(1, 'error_file.php');

        $this->expectOutputRegex("/Fatal error #1 in file error_file.php./");
        PMA_fatalError($message, $params);

        $message = "Fatal error in file %s.";
        $params = 'error_file.php';

        $this->expectOutputRegex("/Fatal error in file error_file.php./");
        PMA_fatalError($message, $params);
    }

}
