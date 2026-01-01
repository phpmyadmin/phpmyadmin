<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Database;

use PhpMyAdmin\Column;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\Search;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Search::class)]
class SearchTest extends AbstractTestCase
{
    protected Search $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Current::$database = 'pma';
        $_POST = [];

        //mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::any())
            ->method('getColumns')
            ->with('pma', 'table1')
            ->willReturn([
                new Column('column1', '', null, false, '', null, '', '', ''),
                new Column('column2', '', null, false, '', null, '', '', ''),
            ]);

        $dbi->expects(self::any())
            ->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        DatabaseInterface::$instance = $dbi;
        $this->object = new Search($dbi, 'pma_test', new Template());
        // $this->object->setSearchParams(new \PhpMyAdmin\Http\ServerRequest());
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
     * @param string $type       type
     * @param string $includeHex includeHex
     * @param string $expected   expected
     */
    #[DataProvider('searchTypesAndOptions')]
    public function testGetWhereClause(string $type, string $includeHex, string $expected): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody([
                'criteriaSearchType' => $type,
                'criteriaSearchString' => 'search string',
                'criteriaSearchOptionIncludeHex' => $includeHex,
            ]);

        $this->object = new Search(DatabaseInterface::getInstance(), 'pma_test', new Template());
        $this->object->setSearchParams($request);
        self::assertSame(
            $expected,
            $this->callFunction(
                $this->object,
                Search::class,
                'getWhereClause',
                ['table1'],
            ),
        );
    }

    /**
     * Data provider for testGetWhereClause
     *
     * @return array<array<string>>
     */
    public static function searchTypesAndOptions(): array
    {
        return [
            [
                '1',
                '0',
                " WHERE (CONVERT(`column1` USING utf8) LIKE '%search%'"
                . " OR CONVERT(`column2` USING utf8) LIKE '%search%') "
                . " OR  (CONVERT(`column1` USING utf8) LIKE '%string%'"
                . " OR CONVERT(`column2` USING utf8) LIKE '%string%')",
            ],
            [
                '2',
                '0',
                " WHERE (CONVERT(`column1` USING utf8) LIKE '%search%'"
                . " OR CONVERT(`column2` USING utf8) LIKE '%search%') "
                . " AND  (CONVERT(`column1` USING utf8) LIKE '%string%'"
                . " OR CONVERT(`column2` USING utf8) LIKE '%string%')",
            ],
            [
                '3',
                '0',
                " WHERE (CONVERT(`column1` USING utf8) LIKE '%search string%'"
                . " OR CONVERT(`column2` USING utf8) LIKE '%search string%')",
            ],
            [
                '4',
                '0',
                " WHERE (CONVERT(`column1` USING utf8) LIKE 'search string'"
                . " OR CONVERT(`column2` USING utf8) LIKE 'search string')",
            ],
            [
                '5',
                '0',
                " WHERE (CONVERT(`column1` USING utf8) REGEXP 'search string'"
                . " OR CONVERT(`column2` USING utf8) REGEXP 'search string')",
            ],
            [
                '1',
                '1',
                " WHERE (CONVERT(`column1` USING utf8) LIKE '%search%'"
                . " OR HEX(`column1`) LIKE '%search%'"
                . " OR CONVERT(`column2` USING utf8) LIKE '%search%'"
                . " OR HEX(`column2`) LIKE '%search%') "
                . " OR  (CONVERT(`column1` USING utf8) LIKE '%string%'"
                . " OR HEX(`column1`) LIKE '%string%'"
                . " OR CONVERT(`column2` USING utf8) LIKE '%string%'"
                . " OR HEX(`column2`) LIKE '%string%')",
            ],
            [
                '2',
                '1',
                " WHERE (CONVERT(`column1` USING utf8) LIKE '%search%'"
                . " OR HEX(`column1`) LIKE '%search%'"
                . " OR CONVERT(`column2` USING utf8) LIKE '%search%'"
                . " OR HEX(`column2`) LIKE '%search%') "
                . " AND  (CONVERT(`column1` USING utf8) LIKE '%string%'"
                . " OR HEX(`column1`) LIKE '%string%'"
                . " OR CONVERT(`column2` USING utf8) LIKE '%string%'"
                . " OR HEX(`column2`) LIKE '%string%')",
            ],
            [
                '3',
                '1',
                " WHERE (CONVERT(`column1` USING utf8) LIKE '%search string%'"
                . " OR HEX(`column1`) LIKE '%search string%'"
                . " OR CONVERT(`column2` USING utf8) LIKE '%search string%'"
                . " OR HEX(`column2`) LIKE '%search string%')",
                'on',
            ],
            [
                '4',
                '1',
                " WHERE (CONVERT(`column1` USING utf8) LIKE 'search string'"
                . " OR HEX(`column1`) LIKE 'search string'"
                . " OR CONVERT(`column2` USING utf8) LIKE 'search string'"
                . " OR HEX(`column2`) LIKE 'search string')",
            ],
            [
                '5',
                '1',
                " WHERE (CONVERT(`column1` USING utf8) REGEXP 'search string'"
                . " OR HEX(`column1`) REGEXP 'search string'"
                . " OR CONVERT(`column2` USING utf8) REGEXP 'search string'"
                . " OR HEX(`column2`) REGEXP 'search string')",
            ],
        ];
    }

    /**
     * Test for getSearchSqls
     */
    public function testGetSearchSqls(): void
    {
        self::assertSame(
            [
                'select_columns' => 'SELECT * FROM `pma`.`table1` WHERE FALSE',
                'select_count' => 'SELECT COUNT(*) AS `count` FROM `pma`.`table1` WHERE FALSE',
                'delete' => 'DELETE FROM `pma`.`table1` WHERE FALSE',
            ],
            $this->callFunction(
                $this->object,
                Search::class,
                'getSearchSqls',
                ['table1'],
            ),
        );
    }

    /**
     * Test for getSearchResults
     */
    public function testGetSearchResults(): void
    {
        self::assertStringContainsString(
            'Search results for "<em></em>" :',
            $this->object->getSearchResults(),
        );
    }

    /**
     * Test for getSelectionForm
     */
    public function testGetMainHtml(): void
    {
        $main = $this->object->getMainHtml();

        // test selection form
        self::assertStringContainsString('<form', $main);
        self::assertStringContainsString(
            '<button id="togglesearchformlink" class="btn btn-primary my-1"></button>',
            $main,
        );
        self::assertStringContainsString('criteriaSearchType', $main);

        // test result divs
        self::assertStringContainsString('<div id="table-info"', $main);
        self::assertStringContainsString('<a id="table-link"', $main);
        self::assertStringContainsString('<div id="browse-results"', $main);
        self::assertStringContainsString('<div id="sqlqueryform"', $main);
        self::assertStringContainsString('<button class="btn btn-secondary" id="togglequerybox"', $main);
    }
}
