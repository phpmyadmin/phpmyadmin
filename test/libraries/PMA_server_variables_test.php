<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for server_variables.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/server_variables.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/js_escape.lib.php';

/**
 * class PMA_ServerVariables_Test
 *
 * this class is for testing server_variables.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_ServerVariables_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for setUp
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
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = array();
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['cfg']['Server']['host'] = "localhost";
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');

        $GLOBALS['table'] = "table";
        $GLOBALS['pmaThemeImage'] = 'image';

        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        //this data is needed when PMA_ServerStatusData constructs
        $server_session_variable = array(
            "auto_increment_increment" => "1",
            "auto_increment_offset" => "13",
            "automatic_sp_privileges" => "ON",
            "back_log" => "50",
            "big_tables" => "OFF",
        );

        $server_global_variables = array(
            "auto_increment_increment" => "0",
            "auto_increment_offset" => "12"
        );

        $fetchResult = array(
            array(
                "SHOW SESSION VARIABLES;",
                0,
                1,
                null,
                0,
                $server_session_variable
            ),
            array(
                "SHOW GLOBAL VARIABLES;",
                0,
                1,
                null,
                0,
                $server_global_variables
            )
        );

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Test for PMA_formatVariable
     *
     * @return void
     */
    public function testPMAFormatVariable()
    {
        //Call the test function
        $name_for_value_byte = "binlog_cache_size";
        $name_for_value_not_byte = "auto_increment_increment";
        $name_for_value_not_num = "PMA_key";

        $variable_doc_links = PMA_getArrayForDocumentLinks();

        //name is_numeric and the value type is byte
        $this->assertEquals(
            '<abbr title="3">3 B</abbr>',
            PMA_formatVariable($name_for_value_byte, "3", $variable_doc_links)
        );

        //name is_numeric and the value type is not byte
        $this->assertEquals(
            '3',
            PMA_formatVariable($name_for_value_not_byte, "3", $variable_doc_links)
        );

        //value is not a number
        $this->assertEquals(
            'value',
            PMA_formatVariable($name_for_value_not_num, "value", $variable_doc_links)
        );
    }

    /**
     * Test for PMA_getHtmlForLinkTemplates
     *
     * @return void
     */
    public function testPMAGetHtmlForLinkTemplates()
    {
        //Call the test function
        $html = PMA_getHtmlForLinkTemplates();
        $url = 'server_variables.php' . PMA_URL_getCommon(array());

        //validate 1: URL
        $this->assertContains(
            $url,
            $html
        );
        //validate 2: images
        $this->assertContains(
            PMA_Util::getIcon('b_save.png', __('Save')),
            $html
        );
        $this->assertContains(
            PMA_Util::getIcon('b_close.png', __('Cancel')),
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForServerVariables
     *
     * @return void
     */
    public function testPMAGetHtmlForServerVariables()
    {
        //Call the test function
        $_REQUEST['filter'] = "auto-commit";
        $variable_doc_links = PMA_getArrayForDocumentLinks();

        $html = PMA_getHtmlForServerVariables($variable_doc_links);

        //validate 1: Filters
        $this->assertContains(
            '<legend>' . __('Filters') . '</legend>',
            $html
        );
        $this->assertContains(
            __('Containing the word:'),
            $html
        );
        $this->assertContains(
            $_REQUEST['filter'],
            $html
        );

        //validate 2: Server Variables
        $this->assertContains(
            '<table id="serverVariables" class="data filteredData noclick">',
            $html
        );
        $this->assertContains(
            __('Variable'),
            $html
        );
        $this->assertContains(
            __('Global value'),
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForServerVariablesItems
     *
     * @return void
     */
    public function testPMAGetHtmlForServerVariablesItems()
    {
        //Call the test function
        $variable_doc_links = PMA_getArrayForDocumentLinks();

        $html = PMA_getHtmlForServerVariablesItems($variable_doc_links);

        //validate 1: variable: auto_increment_increment
        $name = "auto_increment_increment";
        $value = htmlspecialchars(str_replace('_', ' ', $name));
        $this->assertContains(
            $value,
            $html
        );

        //validate 2: variable: auto_increment_offset
        $name = "auto_increment_offset";
        $value = htmlspecialchars(str_replace('_', ' ', $name));
        $this->assertContains(
            $value,
            $html
        );

        $value = PMA_formatVariable($name, "12", $variable_doc_links);
        $this->assertContains(
            $value,
            $html
        );

        //validate 3: variables
        $this->assertContains(
            __('Session value'),
            $html
        );

        $value = PMA_formatVariable($name, "13", $variable_doc_links);
        $this->assertContains(
            $value,
            $html
        );
    }
}
