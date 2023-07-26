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
     * @psalm-param list<list<string>> $result
     *
     * @dataProvider providerForTestGetMatchedRows
     */
    public function testGetMatchedRows(string $sqlQuery, string $simulatedQuery, array $result): void
    {
        $GLOBALS['db'] = 'PMA';
        $object = new SimulateDml($this->dbi);
        $parser = new Parser($sqlQuery);
        $this->dummyDbi->addSelectDb('PMA');
        $this->dummyDbi->addResult($simulatedQuery, $result);

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
            'matched_rows' => count($result),
            'matched_rows_url' => $matchedRowsUrl,
        ], $simulatedData);
    }

    /**
     * @return array<string, array{string, string, list<list<string>>}>
     */
    public function providerForTestGetMatchedRows(): array
    {
        return [
            'update statement' => [
                'UPDATE `table_1` SET `id` = 20 WHERE `id` > 10',
                'SELECT `id` FROM `table_1` WHERE (`id` > 10) AND (NOT `id` <=> (20))',
                [['11'], ['12']],
            ],
            'delete statement' => [
                'DELETE FROM `table_1` WHERE `id` > 10',
                'SELECT * FROM `table_1` WHERE `id` > 10',
                [['row1'], ['row2']],
            ],
            'update statement_false_condition' => [
                'UPDATE `table_1` SET `id` = 20 WHERE 0',
                'SELECT `id` FROM `table_1` WHERE (0) AND (NOT `id` <=> (20))',
                [],
            ],
            'delete statement_false_condition' => [
                'DELETE FROM `table_1` WHERE 0',
                'SELECT * FROM `table_1` WHERE 0',
                [],
            ],
        ];
    }
}
