<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for structure.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/structure.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/Table.class.php';

/**
 * PMA_Structure_Test class
 *
 * this class is for testing structure.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_Structure_Test extends PHPUnit_Framework_TestCase
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
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = array();
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $GLOBALS['table'] = "table";
        $GLOBALS['pmaThemeImage'] = 'image';

        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();
    }

    /**
     * Test for PMA_getHtmlForActionLinks
     *
     * @return void
     */
    public function testPMAGetHtmlForActionLinks()
    {
        $current_table = array(
            'TABLE_ROWS' => 3,
            'TABLE_NAME' => 'name1',
            'TABLE_COMMENT' => 'This is a test comment'
        );
        $table_is_view = false;
        $tbl_url_query = 'tbl_url_query';
        $titles = array(
            'Browse' => 'Browse1',
            'NoBrowse' => 'NoBrowse1',
            'Search' => 'Search1',
            'NoSearch' => 'NoSearch1',
            'Empty' => 'Empty1',
            'NoEmpty' => 'NoEmpty1',
        );;
        $truename = 'truename';
        $db_is_system_schema = null;
        $url_query = 'url_query';

        //$table_is_view = true;
        list(
            $browse_table, $search_table,$browse_table_label,
            $empty_table, $tracking_icon
        ) = PMA_getHtmlForActionLinks(
            $current_table, $table_is_view, $tbl_url_query,
            $titles, $truename, $db_is_system_schema, $url_query
        );

        //$browse_table
        $this->assertContains(
            $titles['Browse'],
            $browse_table
        );

        //$search_table
        $this->assertContains(
            $titles['Search'],
            $search_table
        );
        $this->assertContains(
            $tbl_url_query,
            $search_table
        );

        //$browse_table_label
        $this->assertContains(
            $tbl_url_query,
            $browse_table_label
        );

        //$empty_table
        $this->assertContains(
            $tbl_url_query,
            $empty_table
        );
        $this->assertContains(
            urlencode(
                'TRUNCATE ' . PMA_Util::backquote($current_table['TABLE_NAME'])
            ),
            $empty_table
        );
        $this->assertContains(
            $titles['Empty'],
            $empty_table
        );

        //$table_is_view = false;
        $current_table = array(
            'TABLE_ROWS' => 0,
            'TABLE_NAME' => 'name1',
            'TABLE_COMMENT' => 'This is a test comment'
        );
        $table_is_view = false;
        list(
            $browse_table, $search_table,$browse_table_label,
            $empty_table, $tracking_icon
        ) = PMA_getHtmlForActionLinks(
            $current_table, $table_is_view, $tbl_url_query,
            $titles, $truename, $db_is_system_schema, $url_query
        );

        //$browse_table
        $this->assertContains(
            $titles['NoBrowse'],
            $browse_table
        );

        //$search_table
        $this->assertContains(
            $titles['NoSearch'],
            $search_table
        );

        //$browse_table_label
        $this->assertContains(
            $tbl_url_query,
            $browse_table_label
        );
        $this->assertContains(
            $titles['NoEmpty'],
            $empty_table
        );
    }

    /**
     * Test for PMA_getTableDropQueryAndMessage
     *
     * @return void
     */
    public function testPMAGetTableDropQueryAndMessage()
    {
        $current_table = array(
            'TABLE_ROWS' => 3,
            'TABLE_NAME' => 'name1',
            'ENGINE' => 'ENGINE1',
        );
        $table_is_view = false;

        list($drop_query, $drop_message) = PMA_getTableDropQueryAndMessage(
            $table_is_view, $current_table
        );

        //$drop_query
        $ret = "DROP TABLE `name1`";
        $this->assertEquals(
            $ret,
            $drop_query
        );

        //$drop_message
        $ret = "Table name1 has been dropped.";
        $this->assertEquals(
            $ret,
            $drop_message
        );
    }

    /**
     * Test for PMA_getHtmlShowCreate
     *
     * @return void
     */
    public function testPMAGetHtmlShowCreate()
    {
        //mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $db = 'PMA';
        $table = 'PMA_Table';
        $is_view_query = "SELECT TABLE_NAME
            FROM information_schema.VIEWS
            WHERE TABLE_SCHEMA = '" . PMA_Util::sqlAddSlashes($db) . "'
                AND TABLE_NAME = '" . PMA_Util::sqlAddSlashes($table) . "'";

        $show_create_query = 'SHOW CREATE TABLE ' . PMA_Util::backquote($db) . '.'
            . PMA_Util::backquote($table);
        $expected_result = 'CREATE TABLE `PMA_Table` ( '
            . '`id` numeric '
            . ') ENGINE=InnoDB DEFAULT CHARSET=latin1';

        $dbi->expects($this->any())
            ->method('fetchResult')
            ->with($is_view_query)
            ->will($this->returnValue(false));

        $dbi->expects($this->any())
            ->method('fetchSingleRow')
            ->with($show_create_query)
            ->will(
                $this->returnValue(
                    array(
                        'Table' => 'PMA_Table',
                        'Create Table' => $expected_result
                    )
                )
            );

        $GLOBALS['dbi'] = $dbi;

        $output = PMA_getHtmlShowCreate($db, array($table));

        $this->assertContains(
            'Showing create queries',
            $output
        );

        $this->assertContains(
            '<legend>Tables</legend><table class="show_create">',
            $output
        );

        $this->assertContains(
            '<th>Create Table</th>',
            $output
        );

        $this->assertContains(
            PMA_mimeDefaultFunction($expected_result),
            $output
        );
    }
}
