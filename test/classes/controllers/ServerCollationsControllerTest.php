<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds ServerCollationsControllerTest class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
//$GLOBALS
use PMA\libraries\Theme;
use PMA\libraries\controllers\server\ServerCollationsController;

$GLOBALS['server'] = 1;
$GLOBALS['is_superuser'] = false;
$GLOBALS['cfg']['ServerDefault'] = 1;
$GLOBALS['url_query'] = "url_query";
$GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
$GLOBALS['lang'] = "en";
$GLOBALS['text_dir'] = "text_dir";
$GLOBALS['cfg']['Server'] = array(
    'DisableIS' => false
);
//$_SESSION

$_SESSION['PMA_Theme'] = Theme::load('./themes/pmahomme');


require_once 'libraries/url_generating.lib.php';


require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/mysql_charsets.inc.php';
require_once 'test/PMATestCase.php';

/**
 * Tests for ServerCollationsController class
 *
 * @package PhpMyAdmin-test
 */
class ServerCollationsControllerTest extends PMATestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    public function setUp()
    {
        //$_REQUEST
        $_REQUEST['log'] = "index1";
        $_REQUEST['pos'] = 3;

        //$GLOBALS
        $GLOBALS['is_ajax_request'] = true;
        $GLOBALS['table'] = "table";
        $GLOBALS['pmaThemeImage'] = 'image';
    }

    /**
     * Test for PMA_getHtmlForCharsets
     *
     * @return void
     */
    public function testPMAGetHtmlForCharsets()
    {
        $mysql_charsets = array("armscii8", "ascii", "big5", "binary");
        $mysql_collations = array(
            "armscii8" => array("armscii8"),
            "ascii" => array("ascii"),
            "big5" => array("big5"),
            "binary" => array("binary"),
        );
        $mysql_charsets_descriptions = array(
            "armscii8" => "PMA_armscii8_general_ci",
            "ascii" => "PMA_ascii_general_ci",
            "big5" => "PMA_big5_general_ci",
            "binary" => "PMA_binary_general_ci",
        );
        $mysql_default_collations = array(
            "armscii8" => "armscii8",
            "ascii" => "ascii",
            "big5" => "big5",
            "binary" => "binary",
        );
        $mysql_collations_available = array(
            "armscii8" => true,
            "ascii" => true,
            "big5" => true,
            "binary" => true,
        );

        $class = new ReflectionClass('\PMA\libraries\controllers\server\ServerCollationsController');
        $method = $class->getMethod('_getHtmlForCharsets');
        $method->setAccessible(true);

        $ctrl = new ServerCollationsController();
        $html = $method->invoke(
            $ctrl,
            $mysql_charsets,
            $mysql_collations,
            $mysql_charsets_descriptions,
            $mysql_default_collations,
            $mysql_collations_available
        );

        //validate 1: Charset HTML
        $this->assertContains(
            '<div id="div_mysql_charset_collations">',
            $html
        );
        $this->assertContains(
            __('Collation'),
            $html
        );
        $this->assertContains(
            __('Description'),
            $html
        );
        //validate 2: Charset Item
        $this->assertContains(
            '<i>PMA_armscii8_general_ci</i>',
            $html
        );
        $this->assertContains(
            '<td>armscii8</td>',
            $html
        );
        $this->assertContains(
            '<i>PMA_ascii_general_ci</i>',
            $html
        );
        $this->assertContains(
            '<td>ascii</td>',
            $html
        );
        $this->assertContains(
            '<i>PMA_big5_general_ci</i>',
            $html
        );
        $this->assertContains(
            '<td>big5</td>',
            $html
        );
    }
}
