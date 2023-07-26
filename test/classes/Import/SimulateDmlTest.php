<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Import;

use PhpMyAdmin\Core;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Import\SimulateDml;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Url;

use function count;

/**
 * @covers \PhpMyAdmin\Import\SimulateDml
 */
class SimulateDmlTest extends AbstractTestCase
{
    /**
     * @dataProvider providerForTestGetMatchedRows
     */
    public function testGetMatchedRows(string $sqlQuery, string $simulatedQuery, int $expectedMatches): void
    {
        $GLOBALS['db'] = 'PMA';
        $object = new SimulateDml($this->dbi);
        $parser = new Parser($sqlQuery);
        $this->dummyDbi->addSelectDb('PMA');
        $this->dummyDbi->addResult($simulatedQuery, array_fill(0, $expectedMatches, [1]));

        $simulatedData = $object->getMatchedRows($sqlQuery, $parser, $parser->statements[0]);

        $matchedRowsUrl = Url::getFromRoute('/sql', [
            'db' => 'PMA',
            'sql_query' => $simulatedQuery,
            'sql_signature' => Core::signSqlQuery($simulatedQuery),
        ]);

        $this->assertAllSelectsConsumed();
        $this->assertAllQueriesConsumed();
        $this->assertEquals([
            'sql_query' => Generator::formatSql($sqlQuery),
            'matched_rows' => $expectedMatches,
            'matched_rows_url' => $matchedRowsUrl,
        ], $simulatedData);
    }

    /**
     * @return array<string, array{string, string, int}>
     */
    public function providerForTestGetMatchedRows(): array
    {
        return [
            'update statement' => [
                'UPDATE `table_1` SET `id` = 20 WHERE `id` > 10',
                'SELECT 1 FROM `table_1` WHERE (`id` > 10) AND (NOT `id` <=> (20))',
                2,
            ],
            'delete statement' => [
                'DELETE FROM `table_1` WHERE `id` > 10',
                'SELECT 1 FROM `table_1` WHERE `id` > 10',
                2,
            ],
            'update statement_false_condition' => [
                'UPDATE `table_1` SET `id` = 20 WHERE 0',
                'SELECT 1 FROM `table_1` WHERE (0) AND (NOT `id` <=> (20))',
                0,
            ],
            'delete statement_false_condition' => [
                'DELETE FROM `table_1` WHERE 0',
                'SELECT 1 FROM `table_1` WHERE 0',
                0,
            ],
        ];
    }
}
