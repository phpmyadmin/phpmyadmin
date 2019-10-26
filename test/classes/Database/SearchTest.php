<?php
/**
 * Tests for PhpMyAdmin\Database\Search
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Database;

use PhpMyAdmin\Database\Search;
use PhpMyAdmin\Template;
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
    protected function setUp(): void
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'pma';

        //mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())
            ->method('getColumns')
            ->with('pma', 'table1')
            ->will($this->returnValue([
                ['Field' => 'column1'],
                ['Field' => 'column2'],
            ]));

        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->object = new Search($dbi, 'pma_test', new Template());
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->object);
    }

    /**
     * Call protected functions by setting visibility to public.
     *
     * @param string $name   method name
     * @param array  $params parameters for the invocation
     *
     * @return mixed the output from the protected method.
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
     * @param string $type     type
     * @param string $expected expected result
     *
     * @return void
     *
     * @dataProvider searchTypes
     */
    public function testGetWhereClause($type, $expected): void
    {
        $_POST['criteriaSearchType'] = $type;
        $_POST['criteriaSearchString'] = 'search string';

        $this->object = new Search($GLOBALS['dbi'], 'pma_test', new Template());
        $this->assertEquals(
            $expected,
            $this->callProtectedFunction(
                'getWhereClause',
                ['table1']
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
        return [
            [
                '1',
                " WHERE (CONVERT(`column1` USING utf8) LIKE '%search%' OR CONVERT(`column2` USING utf8) LIKE '%search%')  OR  (CONVERT(`column1` USING utf8) LIKE '%string%' OR CONVERT(`column2` USING utf8) LIKE '%string%')",
            ],
            [
                '2',
                " WHERE (CONVERT(`column1` USING utf8) LIKE '%search%' OR CONVERT(`column2` USING utf8) LIKE '%search%')  AND  (CONVERT(`column1` USING utf8) LIKE '%string%' OR CONVERT(`column2` USING utf8) LIKE '%string%')",
            ],
            [
                '3',
                " WHERE (CONVERT(`column1` USING utf8) LIKE '%search string%' OR CONVERT(`column2` USING utf8) LIKE '%search string%')",
            ],
            [
                '4',
                " WHERE (CONVERT(`column1` USING utf8) LIKE 'search string' OR CONVERT(`column2` USING utf8) LIKE 'search string')",
            ],
            [
                '5',
                " WHERE (CONVERT(`column1` USING utf8) REGEXP 'search string' OR CONVERT(`column2` USING utf8) REGEXP 'search string')",
            ],
        ];
    }

    /**
     * Test for _getSearchSqls
     *
     * @return void
     */
    public function testGetSearchSqls()
    {
        $this->assertEquals(
            [
                'select_columns' => 'SELECT *  FROM `pma`.`table1` WHERE FALSE',
                'select_count' => 'SELECT COUNT(*) AS `count` FROM `pma`.`table1` ' .
                    'WHERE FALSE',
                'delete' => 'DELETE FROM `pma`.`table1` WHERE FALSE',
            ],
            $this->callProtectedFunction(
                'getSearchSqls',
                ['table1']
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
        $this->assertStringContainsString(
            'Search results for "<em></em>" :',
            $this->object->getSearchResults()
        );
    }

    /**
     * Test for getSelectionForm
     *
     * @return void
     */
    public function testGetMainHtml()
    {
        $main = $this->object->getMainHtml();

        // test selection form
        $this->assertStringContainsString('<form', $main);
        $this->assertStringContainsString('<a id="togglesearchformlink">', $main);
        $this->assertStringContainsString('criteriaSearchType', $main);

        // test result divs
        $this->assertStringContainsString(
            '<div id="table-info"',
            $main
        );
        $this->assertStringContainsString(
            '<a id="table-link"',
            $main
        );
        $this->assertStringContainsString(
            '<div id="browse-results"',
            $main
        );
        $this->assertStringContainsString(
            '<div id="sqlqueryform"',
            $main
        );
        $this->assertStringContainsString(
            '<a id="togglequerybox"',
            $main
        );
    }
}
