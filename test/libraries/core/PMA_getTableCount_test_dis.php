<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_getTableCount_test from core.lib.php
 * PMA_getTableCount_test returns count of tables in given db
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/vendor_config.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/core.lib.php';
require_once 'libraries/Util.class.php';

require_once 'libraries/js_escape.lib.php';
require_once 'libraries/select_lang.lib.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/config.default.php';
require_once 'libraries/Config.class.php';

require_once 'libraries/url_generating.lib.php';

require_once 'libraries/Table.class.php';
require_once 'libraries/database_interface.lib.php';
require_once 'libraries/php-gettext/gettext.inc';

require_once 'config.sample.inc.php';

class PMA_getTableCount_test extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $GLOBALS['PMA_Config'] = new PMA_Config();
        $GLOBALS['PMA_Config']->enableBc();
//        $GLOBALS['cfg']['Server'] = array(
//            'host' => 'host',
//            'verbose' => 'verbose',
//            'extension' => 'mysql'
//        );
        $GLOBALS['cfg']['OBGzip'] = false;
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $_SESSION[' PMA_token '] = 'token';
        $GLOBALS['pmaThemeImage'] = 'theme/';
        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
    }

    function testTableCount()
    {
        $GLOBALS['cfg']['Server']['extension'] = 'mysql';
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['user'] = 'root';

        $this->assertEquals(5, PMA_getTableCount('meddb'));
        $this->assertTrue(true);
    }
}
