<?php
/**
 * Tests for PhpMyAdmin\Database\Search
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Database;

use PhpMyAdmin\Database\Search;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Theme;
use ReflectionClass;

/**
 * Tests for database search.
 *
 * @package PhpMyAdmin-test
 */
class SearchTest extends PmaTestCase
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
        $this->object = new Search('pma_test');
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'pma';

        //mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())
            ->method('getColumns')
            ->with('pma', 'table1')
            ->will($this->returnValue(array(
                array('Field' => 'column1'),
                array('Field' => 'column2'),
            )));

        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
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
    private function callProtectedFunction($name, $params)
    {
        $class = new ReflectionClass(Search::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->object, $params);
    }

    /**
     * Test for generating where clause for different search types
     *
     * @dataProvider searchTypes
     */
    public function testGetWhereClause($type, $expected)
    {
        $_REQUEST['criteriaSearchType'] = $type;
        $_REQUEST['criteriaSearchString'] = 'search string';

        $this->object = new Search('pma_test');
        $this->assertEquals(
            $expected,
            $this->callProtectedFunction(
                'getWhereClause',
                array('table1')
            )
        );
    }

    /**
     * Data provider for testGetWhereClause
     *
     * @return array
     */
    public function searchTypes()
    {
        return array(
            array(
                '1',
                " WHERE (CONVERT(`column1` USING utf8) LIKE '%search%' OR CONVERT(`column2` USING utf8) LIKE '%search%')  OR  (CONVERT(`column1` USING utf8) LIKE '%string%' OR CONVERT(`column2` USING utf8) LIKE '%string%')"
            ),
            array(
                '2',
                " WHERE (CONVERT(`column1` USING utf8) LIKE '%search%' OR CONVERT(`column2` USING utf8) LIKE '%search%')  AND  (CONVERT(`column1` USING utf8) LIKE '%string%' OR CONVERT(`column2` USING utf8) LIKE '%string%')"
            ),
            array(
                '3',
                " WHERE (CONVERT(`column1` USING utf8) LIKE '%search string%' OR CONVERT(`column2` USING utf8) LIKE '%search string%')"
            ),
            array(
                '4',
                " WHERE (CONVERT(`column1` USING utf8) LIKE 'search string' OR CONVERT(`column2` USING utf8) LIKE 'search string')"
            ),
            array(
                '5',
                " WHERE (CONVERT(`column1` USING utf8) REGEXP 'search string' OR CONVERT(`column2` USING utf8) REGEXP 'search string')"
            ),
        );
    }

    /**
     * Test for _getSearchSqls
     *
     * @return void
     */
    public function testGetSearchSqls()
    {
        $this->assertEquals(
            array (
                'select_columns' => 'SELECT *  FROM `pma`.`table1` WHERE FALSE',
                'select_count' => 'SELECT COUNT(*) AS `count` FROM `pma`.`table1` ' .
                    'WHERE FALSE',
                'delete' => 'DELETE FROM `pma`.`table1` WHERE FALSE'
            ),
            $this->callProtectedFunction(
                'getSearchSqls',
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
     * Test for getResultsRow
     *
     * @param string $table         Tables on which search is to be performed
     * @param array  $newSearchSqls Contains SQL queries
     * @param string $expected      Expected HTML output
     *
     * @return void
     *
     * @dataProvider providerForTestGetResultsRow
     */
    public function testGetResultsRow($table, $newSearchSqls, $needle)
    {
        $haystack = $this->_callProtectedFunction(
            'getResultsRow',
            array($table, $newSearchSqls, 2)
        );
        $this->assertContains($needle, $haystack);
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
                array('select_columns' => 'column1', 'delete' => 'column2'),
                '2 matches in <strong>table1</strong>'
            ),
            array(
                'table1',
                array('select_columns' => 'column1', 'delete' => 'column2'),
                'data-table-name="table1"'
            ),
            array(
                'table1',
                array('select_columns' => 'column1', 'delete' => 'column2'),
                'data-browse-sql="column1"'
            ),
            array(
                'table1',
                array('select_columns' => 'column1', 'delete' => 'column2'),
                'data-delete-sql="column2"'
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
        $form = $this->object->getSelectionForm();
        $this->assertContains('<form', $form);
        $this->assertContains('<a id="togglesearchformlink">', $form);
        $this->assertContains('criteriaSearchType', $form);
    }

    /**
     * Test for getResultDivs
     *
     * @return void
     */
    public function testGetResultDivs()
    {
        $actual = $this->callProtectedFunction(
            'getResultDivs',
            array()
        );
        $this->assertContains(
            '<div id="table-info"',
            $actual
        );
        $this->assertContains(
            '<a id="table-link"',
            $actual
        );
        $this->assertContains(
            '<div id="browse-results"',
            $actual
        );
        $this->assertContains(
            '<div id="sqlqueryform"',
            $actual
        );
        $this->assertContains(
            '<a id="togglequerybox"',
            $actual
        );
    }
}
