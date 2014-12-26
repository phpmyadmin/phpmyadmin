<?php
/**
 * Tests for DbSearch.class.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/DbSearch.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Util.class.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/core.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Tracker.class.php';

/**
 * Tests for database search.
 *
 * @package PhpMyAdmin-test
 */
class PMA_DbSearch_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $this->object = new PMA_DbSearch('pma_test');
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['ShowHint'] = true;
        $GLOBALS['db'] = 'pma';
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * Call protected functions by setting visibility to public.
     *
     * @param string $name   method name
     * @param array  $params parameters for the invocation
     *
     * @return the output from the protected method.
     */
    private function _callProtectedFunction($name, $params)
    {
        $class = new ReflectionClass('PMA_DbSearch');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->object, $params);
    }

    /**
     * Test for _getSearchSqls
     *
     * @return void
     */
    public function testGetSearchSqls()
    {
        //mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())
            ->method('getColumns')
            ->with('pma', 'table1')
            ->will($this->returnValue(array()));

        $GLOBALS['dbi'] = $dbi;

        $this->assertEquals(
            array (
                'select_columns' => 'SELECT *  FROM `pma`.`table1` WHERE FALSE',
                'select_count' => 'SELECT COUNT(*) AS `count` FROM `pma`.`table1` ' .
                    'WHERE FALSE',
                'delete' => 'DELETE FROM `pma`.`table1` WHERE FALSE'
            ),
            $this->_callProtectedFunction(
                '_getSearchSqls',
                array('table1')
            )
        );
    }

    /**
     * Test for getSearchResults
     *
     * @return void
     */
    public function testGetSearchResults()
    {
        $this->assertEquals(
            '<br /><table class="data"><caption class="tblHeaders">Search results '
            . 'for "<i></i>" :</caption></table>',
            $this->object->getSearchResults()
        );
    }

    /**
     * Test for _getResultsRow
     *
     * @param string $each_table    Tables on which search is to be performed
     * @param array  $newsearchsqls Contains SQL queries
     * @param bool   $odd_row       For displaying contrasting table rows
     * @param string $output        Expected HTML output
     *
     * @return void
     *
     * @dataProvider providerForTestGetResultsRow
     */
    public function testGetResultsRow(
        $each_table, $newsearchsqls, $odd_row, $output
    ) {

        $this->assertEquals(
            $output,
            $this->_callProtectedFunction(
                '_getResultsRow',
                array($each_table, $newsearchsqls, $odd_row, 2)
            )
        );
    }

    /**
     * Data provider for testGetResultRow
     *
     * @return array provider for testGetResultsRow
     */
    public function providerForTestGetResultsRow()
    {
        return array(
            array(
                'table1',
                array(
                    'SELECT *  FROM `pma`.`table1` WHERE FALSE',
                    'SELECT COUNT(*) AS `count` FROM `pma`.`table1` WHERE FALSE',
                    'select_count' => 2,
                    'select_columns' => 'column1',
                    'delete' => 'column2'
                ),
                true,
                '<tr class="noclick odd"><td>2 matches in <strong>table1</strong>'
                . '</td><td><a name="browse_search" class="ajax" '
                . 'href="sql.php?db=pma&amp;table'
                . '=table1&amp;goto=db_sql.php&amp;pos=0&amp;is_js_confirmed=0&amp;'
                . 'sql_query=column1&amp;server=0&amp;lang=en&amp;'
                . 'collation_connection=utf-8&amp;token=token" '
                . 'onclick="loadResult(\'sql.php?db=pma&amp;table=table1&amp;goto='
                . 'db_sql.php&amp;pos=0&amp;is_js_confirmed=0&amp;sql_query=column1'
                . '&amp;server=0&amp;lang=en&amp;collation_connection=utf-8'
                . '&amp;token=token\',\'table1\',\'?db=pma'
                . '&amp;table=table1&amp;server=0&amp;lang=en'
                . '&amp;collation_connection=utf-8&amp;token=token\');'
                . 'return false;" >Browse</a></td><td>'
                . '<a name="delete_search" class="ajax" href'
                . '="sql.php?db=pma&amp;table=table1&amp;goto=db_sql.php&amp;pos=0'
                . '&amp;is_js_confirmed=0&amp;sql_query=column2&amp;server=0&amp;'
                . 'lang=en&amp;collation_connection=utf-8&amp;token=token"'
                . ' onclick="deleteResult(\'sql.php?db=pma'
                . '&amp;table=table1&amp;goto=db_sql.php&amp;pos=0&amp;is_js_'
                . 'confirmed=0&amp;sql_query=column2&amp;server=0&amp;lang=en'
                . '&amp;collation_connection=utf-8&amp;'
                . 'token=token\' , \'Delete the matches for the table1 table?\');'
                . 'return false;">Delete</a></td></tr>'
            )
        );
    }

    /**
     * Test for getSelectionForm
     *
     * @return void
     */
    public function testGetSelectionForm()
    {
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $GLOBALS['pmaThemeImage'] = 'themes/dot.gif';
        $this->assertEquals(
            '<a id="db_search"></a><form id="db_search_form" class="ajax" '
            . 'method="post" action="db_search.php" name="db_search">'
            . '<input type="hidden" name="db" value="pma" />'
            . '<input type="hidden" name="lang" value="en" />'
            . '<input type="hidden" name="collation_connection" value="utf-8" />'
            . '<input type="hidden" name="token" value="token" />'
            . '<fieldset><legend>Search in database</legend><table class='
            . '"formlayout"><tr><td>Words or values to search for (wildcard: "%"):'
            . '</td><td><input type="text" name="criteriaSearchString" size="60" '
            . 'value="" /></td></tr><tr><td class="right vtop">Find:</td><td><input '
            . 'type="radio" name="criteriaSearchType" id="criteriaSearchType_1" '
            . 'value="1" checked="checked" />' . "\n"
            . '<label for="criteriaSearchType_1">at least one of the words<span '
            . 'class="pma_hint"><img src="themes/dot.gifb_help.png" title="" alt="" '
            . '/><span class="hide">Words are separated by a space character (" ").'
            . '</span></span></label><br />' . "\n"
            . '<input type="radio" name="criteriaSearchType" id="criteriaSearchType'
            . '_2" value="2" />' . "\n"
            . '<label for="criteriaSearchType_2">all words<span class="pma_hint">'
            . '<img src="themes/dot.gifb_help.png" title="" alt="" /><span class'
            . '="hide">Words are separated by a space character (" ").</span></span>'
            . '</label><br />' . "\n"
            . '<input type="radio" name="criteriaSearchType" id="criteriaSearchType'
            . '_3" value="3" />' . "\n"
            . '<label for="criteriaSearchType_3">the exact phrase</label><br />'
            . "\n" . '<input type="radio" name="criteriaSearchType" id="criteria'
            . 'SearchType_4" value="4" />' . "\n"
            . '<label for="criteriaSearchType_4">as regular expression <a href='
            . '"./url.php?url=http%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.6%2Fen'
            . '%2Fregexp.html" target='
            . '"mysql_doc"><img src="themes/dot.gifb_help.png" title="Documentation"'
            . ' alt="Documentation" /></a></label><br />' . "\n"
            . '</td></tr><tr><td class="right vtop">Inside tables:</td>'
            . '<td rowspan="2"><select name="criteriaTables[]" size="6" '
            . 'multiple="multiple"><option value="table1">table1</option>'
            . '<option value="table2">table2</option></select></td></tr><tr>'
            . '<td class="right vbottom"><a href="#" onclick="setSelectOptions'
            . '(\'db_search\', \'criteriaTables[]\', true); return false;">Select '
            . 'All</a> &nbsp;/&nbsp;<a href="#" onclick="setSelectOptions'
            . '(\'db_search\', \'criteriaTables[]\', false); return false;">Unselect'
            . ' All</a></td></tr><tr><td class="right">Inside column:</td><td>'
            . '<input type="text" name="criteriaColumnName" size="60"value="" />'
            . '</td></tr></table></fieldset><fieldset class="tblFooters"><input '
            . 'type="submit" name="submit_search" value="Go" id="buttonGo" />'
            . '</fieldset></form><div id="togglesearchformdiv">'
            . '<a id="togglesearchformlink"></a></div>',
            $this->object->getSelectionForm()
        );
    }

    /**
     * Test for getResultDivs
     *
     * @return void
     */
    public function testGetResultDivs()
    {
        $this->assertEquals(
            '<!-- These two table-image and table-link elements display the '
            . 'table name in browse search results  --><div id="table-info">'
            . '<a class="item" id="table-link" ></a></div><div id="browse-results">'
            . '<!-- this browse-results div is used to load the browse and delete '
            . 'results in the db search --></div><br class="clearfloat" />'
            . '<div id="sqlqueryform"><!-- this sqlqueryform div is used to load the'
            . ' delete form in the db search --></div><!--  toggle query box link-->'
            . '<a id="togglequerybox"></a>',
            $this->_callProtectedFunction(
                'getResultDivs',
                array()
            )
        );
    }

}
