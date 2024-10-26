<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Database;

use PhpMyAdmin\Database\Search;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Database\Search
 */
class SearchTest extends AbstractTestCase
{
    /** @var Search */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'pma';
        $GLOBALS['_POST'] = [];

        //mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
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
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Test for generating where clause for different search types
     *
     * @param string $type     type
     * @param string $expected expected result
     *
     * @dataProvider searchTypes
     */
    public function testGetWhereClause(string $type, string $expected): void
    {
        $_POST['criteriaSearchType'] = $type;
        $_POST['criteriaSearchString'] = 'search string';

        $this->object = new Search($GLOBALS['dbi'], 'pma_test', new Template());
        self::assertSame($expected, $this->callFunction(
            $this->object,
            Search::class,
            'getWhereClause',
            ['table1']
        ));
    }

    /**
     * Data provider for testGetWhereClause
     *
     * @return array
     */
    public static function searchTypes(): array
    {
        return [
            [
                '1',
                " WHERE (CONVERT(`column1` USING utf8) LIKE '%search%'"
                . " OR CONVERT(`column2` USING utf8) LIKE '%search%') "
                . " OR  (CONVERT(`column1` USING utf8) LIKE '%string%'"
                . " OR CONVERT(`column2` USING utf8) LIKE '%string%')",
            ],
            [
                '2',
                " WHERE (CONVERT(`column1` USING utf8) LIKE '%search%'"
                . " OR CONVERT(`column2` USING utf8) LIKE '%search%') "
                . " AND  (CONVERT(`column1` USING utf8) LIKE '%string%'"
                . " OR CONVERT(`column2` USING utf8) LIKE '%string%')",
            ],
            [
                '3',
                " WHERE (CONVERT(`column1` USING utf8) LIKE '%search string%'"
                . " OR CONVERT(`column2` USING utf8) LIKE '%search string%')",
            ],
            [
                '4',
                " WHERE (CONVERT(`column1` USING utf8) LIKE 'search string'"
                . " OR CONVERT(`column2` USING utf8) LIKE 'search string')",
            ],
            [
                '5',
                " WHERE (CONVERT(`column1` USING utf8) REGEXP 'search string'"
                . " OR CONVERT(`column2` USING utf8) REGEXP 'search string')",
            ],
        ];
    }

    /**
     * Test for getSearchSqls
     */
    public function testGetSearchSqls(): void
    {
        self::assertSame([
            'select_columns' => 'SELECT * FROM `pma`.`table1` WHERE FALSE',
            'select_count' => 'SELECT COUNT(*) AS `count` FROM `pma`.`table1` WHERE FALSE',
            'delete' => 'DELETE FROM `pma`.`table1` WHERE FALSE',
        ], $this->callFunction(
            $this->object,
            Search::class,
            'getSearchSqls',
            ['table1']
        ));
    }

    /**
     * Test for getSearchResults
     */
    public function testGetSearchResults(): void
    {
        self::assertStringContainsString('Search results for "<em></em>" :', $this->object->getSearchResults());
    }

    /**
     * Test for getSelectionForm
     */
    public function testGetMainHtml(): void
    {
        $main = $this->object->getMainHtml();

        // test selection form
        self::assertStringContainsString('<form', $main);
        self::assertStringContainsString('<a id="togglesearchformlink">', $main);
        self::assertStringContainsString('criteriaSearchType', $main);

        // test result divs
        self::assertStringContainsString('<div id="table-info"', $main);
        self::assertStringContainsString('<a id="table-link"', $main);
        self::assertStringContainsString('<div id="browse-results"', $main);
        self::assertStringContainsString('<div id="sqlqueryform"', $main);
        self::assertStringContainsString('<button class="btn btn-secondary" id="togglequerybox"', $main);
    }
}
