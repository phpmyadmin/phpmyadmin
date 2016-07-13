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
require_once 'libraries/DatabaseInterface.class.php';
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
require_once 'libraries/url_generating.lib.php';

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
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $_POST['zoom_submit'] = 'zoom';

        $GLOBALS['server'] = 1;
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['pmaThemeImage'] = 'themes/dot.gif';
        $GLOBALS['is_ajax_request'] = false;
        $GLOBALS['cfgRelation'] = PMA_getRelationsParam();
        $GLOBALS['PMA_Types'] = new PMA_Types_MySQL();

        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['maxRowPlotLimit'] = 500;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['ActionLinksMode'] = 'both';
        $GLOBALS['cfg']['ForeignKeyMaxLimit'] = 100;
        $GLOBALS['cfg']['InitialSlidersState'] = 'closed';
        $GLOBALS['cfg']['MaxRows'] = 25;
        $GLOBALS['cfg']['TabsMode'] = 'text';

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
        ) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_bin "
        . "COMMENT='Bookmarks'";

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
     * @group medium
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
     * @group medium
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
            __('Find and replace'),
            $form
        );
    }

    /**
     * Test for getSecondaryTabs
     *
     * @return void
     */
    public function testGetSecondaryTabs()
    {
        $tableSearch = new PMA_TableSearch("PMA", "PMA_BookMark", "zoom");
        $html = $tableSearch->getSecondaryTabs();
        $this->assertContains(
            '<ul id="topmenu2">',
            $html
        );
        //sub tabs
        $this->assertContains(
            __('Table search'),
            $html
        );
        $this->assertContains(
            __('Zoom search'),
            $html
        );
        $this->assertContains(
            __('Find and replace'),
            $html
        );
    }

    /**
     * Test for getZoomResultsForm
     *
     * @return void
     */
    public function testGetZoomResultsForm()
    {
        $tableSearch = new PMA_TableSearch("PMA", "PMA_BookMark", "zoom");
        $goto = "http://phpmyadmin.net";
        $data = array("PMAA" => "abc");
        $html = $tableSearch->getZoomResultsForm($goto, $data);
        $this->assertContains(
            '<legend>' . __('Browse/Edit the points') . '</legend>',
            $html
        );
        $this->assertContains(
            htmlspecialchars(json_encode($data)),
            $html
        );

    }

    /**
     * Test for replace
     *
     * @return void
     */
    public function testReplace()
    {
        $tableSearch = new PMA_TableSearch("PMA", "PMA_BookMark", "zoom");
        $columnIndex = 0;
        $find = "Field";
        $replaceWith = "Column";
        $useRegex = false;
        $charSet = "UTF-8";
        $tableSearch->replace(
            $columnIndex, $find, $replaceWith, $useRegex, $charSet
        );

        $sql_query = $GLOBALS['sql_query'];
        $result = "UPDATE `PMA`.`PMA_BookMark` SET `Field1` = "
            . "REPLACE(`Field1`, 'Field', 'Column') "
            . "WHERE `Field1` LIKE '%Field%' COLLATE UTF-8_bin";
        $this->assertEquals(
            $result,
            $sql_query
        );
    }

    /**
     * Test for _getSearchAndReplaceHTML
     *
     * @return void
     */
    public function testGetSearchAndReplaceHTML()
    {
        $tableSearch = new PMA_TableSearch("PMA", "PMA_BookMark", "zoom");
        $html = $tableSearch->_getSearchAndReplaceHTML();
        $this->assertContains(
            __('Find:'),
            $html
        );
        $this->assertContains(
            __('Replace with:'),
            $html
        );

    }

    /**
     * Test for getReplacePreview
     *
     * @return void
     */
    public function testGetReplacePreview()
    {

        $value = array(
                'value',
                'replace_value',
                'count'
        );

        $dbi = $GLOBALS['dbi'];

        $dbi->expects($this->once())
            ->method('fetchResult')
            ->will($this->returnValue(array($value)));

        $GLOBALS['dbi'] = $dbi;

        $tableSearch = new PMA_TableSearch("PMA", "PMA_BookMark", "zoom");
        $columnIndex = 0;
        $find = "Field";
        $replaceWith = "Column";
        $useRegex = false;
        $charSet = "UTF-8";

        $html = $tableSearch->getReplacePreview(
            $columnIndex,
            $find,
            $replaceWith,
            $useRegex,
            $charSet
        );

        $this->assertContains(
            '<form method="post" action="tbl_find_replace.php"',
            $html
        );
        $this->assertContains(
            '<input type="hidden" name="replace" value="true" />',
            $html
        );
        $this->assertContains(
            __('Find and replace - preview'),
            $html
        );
        $this->assertContains(
            __('Original string'),
            $html
        );
        $this->assertContains(
            __('Replaced string'),
            $html
        );

        $this->assertContains(
            '<td>value</td>',
            $html
        );
        $this->assertContains(
            '<td>replace_value</td>',
            $html
        );
        $this->assertContains(
            '<td class="right">count</td>',
            $html
        );
    }

    /**
     * Test for buildSqlQuery
     *
     * @return void
     */
    public function testBuildSqlQueryw()
    {
        $_POST['distinct'] = true;
        $_POST['zoom_submit'] = true;
        $_POST['table'] = "PMA";
        $_POST['orderByColumn'] = "name";
        $_POST['order'] = "asc";
        $_POST['customWhereClause'] = "name='pma'";

        $tableSearch = new PMA_TableSearch("PMA", "PMA_BookMark", "zoom");

        $sql = $tableSearch->buildSqlQuery();
        $result = "SELECT DISTINCT *  FROM `PMA` WHERE name='pma' "
            . "ORDER BY `name` asc";

        $this->assertEquals(
            $result,
            $sql
        );

        unset($_POST['customWhereClause']);
        $sql = $tableSearch->buildSqlQuery();
        $result = "SELECT DISTINCT *  FROM `PMA` ORDER BY `name` asc";
        $this->assertEquals(
            $result,
            $sql
        );

        $_POST['criteriaValues'] = array(
            'value1',
            'value2',
            'value3',
            'value4',
            'value5',
            'value6',
            'value7,value8'
        );
        $_POST['criteriaColumnNames'] = array(
            'name',
            'id',
            'index',
            'index2',
            'index3',
            'index4',
            'index5',
        );
        $_POST['criteriaColumnTypes'] = array(
            'varchar',
            'int',
            'enum',
            'type1',
            'type2',
            'type3',
            'type4'
        );
        $_POST['criteriaColumnCollations'] = array(
            "char1",
            "char2",
            "char3",
            "char4",
            "char5",
            "char6",
            "char7",
        );
        $_POST['criteriaColumnOperators'] = array(
            "!=",
            ">",
            "IS NULL",
            "LIKE %...%",
            "REGEXP ^...$",
            "IN (...)",
            "BETWEEN"
        );

        $sql = $tableSearch->buildSqlQuery();
        $result = "SELECT DISTINCT *  FROM `PMA` WHERE `name` != 'value1'"
            . " AND `id` > value2 AND `index` IS NULL AND `index2` LIKE '%value4%'"
            . " AND `index3` REGEXP ^value5$ AND `index4` IN (value6) AND `index5`"
            . " BETWEEN value7 AND value8 ORDER BY `name` asc";
        $this->assertEquals(
            $result,
            $sql
        );
    }
}
?>
