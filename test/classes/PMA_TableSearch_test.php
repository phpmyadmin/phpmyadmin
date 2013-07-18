<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PMA_TableSearch
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/TableSearch.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/Types.class.php';
require_once 'libraries/relation.lib.php';

/**
 * Tests for PMA_TableSearch
 *
 * @package PhpMyAdmin-test
 */
class PMA_TableSearch_Test extends PHPUnit_Framework_TestCase
{

    /**
     * Setup function for test cases
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        /**
         * SET these to avoid undefined index error
         */
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['maxRowPlotLimit'] = 500;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['MySQLManualType'] = 'viewable';
        $GLOBALS['cfg']['MySQLManualBase'] = 'http://dev.mysql.com/doc/refman';
        $GLOBALS['cfg']['ActionLinksMode'] = 'both';
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $GLOBALS['pmaThemeImage'] = 'themes/dot.gif';
        $GLOBALS['is_ajax_request'] = false;
        $GLOBALS['cfgRelation'] = PMA_getRelationsParam();
        $GLOBALS['PMA_Types'] = new PMA_Types_MySQL();
        $GLOBALS['cfg']['ForeignKeyMaxLimit'] = 100;
        $GLOBALS['cfg']['InitialSlidersState'] = 'closed';
        $GLOBALS['cfg']['MaxRows'] = 25;
        
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        
        $columns =array(
            array(
                'Field' => 'Field1',
                'Type' => 'Type1',
                'Null' => 'Null1',
                'Collation' => 'Collation1',
            ),
            array(
                'Field' => 'Field2',
                'Type' => 'Type2',
                'Null' => 'Null2',
                'Collation' => 'Collation2',
            )
        );
        $dbi->expects($this->any())->method('getColumns')
            ->will($this->returnValue($columns));
        
        $show_create_table = "CREATE TABLE `pma_bookmark` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `dbase` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
        `user` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
        `label` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
        `query` text COLLATE utf8_bin NOT NULL,
        PRIMARY KEY (`id`),
        KEY `foreign_field` (`foreign_db`,`foreign_table`)
        ) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Bookmarks'";
        
        $dbi->expects($this->any())->method('fetchValue')
            ->will($this->returnValue($show_create_table));

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * tearDown function for test cases
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
    
    }

    /**
     * Test for __construct
     *
     * @return void
     */
    public function testConstruct()
    {
        $tableSearch = new PMA_TableSearch("PMA", "PMA_BookMark", "normal");
        $columNames = $tableSearch->getColumnNames();
        $this->assertEquals(
            'Field1',
            $columNames[0]
        );
        $this->assertEquals(
            'Field2',
            $columNames[1]
        );
    }

    /**
     * Test for getSelectionForm
     *
     * @return void
     */
    public function testGetSelectionForm()
    {
    	//$this->_searchType == 'zoom'
        $tableSearch = new PMA_TableSearch("PMA", "PMA_BookMark", "zoom");
        $url_goto = "http://phpmyadmin.net";
        $form = $tableSearch->getSelectionForm($url_goto);
        $this->assertContains(
            '<fieldset id="fieldset_zoom_search">',
            $form
        );
        $this->assertContains(
            'Do a "query by example"',
            $form
        );
        
    	//$this->_searchType == 'normal'
        $tableSearch = new PMA_TableSearch("PMA", "PMA_BookMark", "normal");
        $url_goto = "http://phpmyadmin.net";
        $form = $tableSearch->getSelectionForm($url_goto);
        $this->assertContains(
            '<fieldset id="fieldset_table_search">',
            $form
        );
        $this->assertContains(
            'Do a "query by example"',
            $form
        );
        
    	//$this->_searchType == 'replace'
        $tableSearch = new PMA_TableSearch("PMA", "PMA_BookMark", "replace");
        $url_goto = "http://phpmyadmin.net";
        $form = $tableSearch->getSelectionForm($url_goto);
        $this->assertContains(
            '<fieldset id="fieldset_find_replace">',
            $form
        );
        $this->assertContains(
            __('Find and Replace'),
            $form
        );
    }
}
?>
