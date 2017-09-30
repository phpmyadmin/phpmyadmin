<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds ServerCollationsControllerTest class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Controllers\Server\ServerCollationsController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Theme;
use ReflectionClass;

/*
 * Include to test.
 */
//$GLOBALS
$GLOBALS['server'] = 1;
$GLOBALS['is_superuser'] = false;
$GLOBALS['cfg']['ServerDefault'] = 1;
$GLOBALS['url_query'] = "url_query";
$GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
$GLOBALS['lang'] = "en";
$GLOBALS['text_dir'] = "text_dir";
$GLOBALS['cfg']['Server'] = array(
    'DisableIS' => false
);

require_once 'libraries/server_common.inc.php';

/**
 * Tests for ServerCollationsController class
 *
 * @package PhpMyAdmin-test
 */
class ServerCollationsControllerTest extends PmaTestCase
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
        $GLOBALS['table'] = "table";
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

        $class = new ReflectionClass('\PhpMyAdmin\Controllers\Server\ServerCollationsController');
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
            '<em>PMA_armscii8_general_ci</em>',
            $html
        );
        $this->assertContains(
            '<td>armscii8</td>',
            $html
        );
        $this->assertContains(
            '<em>PMA_ascii_general_ci</em>',
            $html
        );
        $this->assertContains(
            '<td>ascii</td>',
            $html
        );
        $this->assertContains(
            '<em>PMA_big5_general_ci</em>',
            $html
        );
        $this->assertContains(
            '<td>big5</td>',
            $html
        );
    }
}
