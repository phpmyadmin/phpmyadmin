<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for server_collations.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
//$GLOBALS
$GLOBALS['server'] = 1;
$GLOBALS['is_superuser'] = false;
$GLOBALS['cfg']['ServerDefault'] = 1;
$GLOBALS['url_query'] = "url_query";
$GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
$GLOBALS['lang'] = "en";
$GLOBALS['available_languages']= array(
    "en" => array("English", "US-ENGLISH"),
    "ch" => array("Chinese", "TW-Chinese")
);
$GLOBALS['text_dir'] = "text_dir";
$GLOBALS['cfg']['DBG']['sql'] = false;
$GLOBALS['cfg']['Server'] = array(
    'DisableIS' => false
);
//$_SESSION
require_once 'libraries/Theme.class.php';
$_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');

require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/mysql_charsets.inc.php';
require_once 'libraries/server_collations.lib.php';

/**
 * PMA_ServerBinlog_Test class
 *
 * this class is for testing server_collations.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_ServerCollations_Test extends PHPUnit_Framework_TestCase
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
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['is_ajax_request'] = true;
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = array();
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;

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

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;

        //Call the test function
        $html = PMA_getHtmlForCharsets(
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
