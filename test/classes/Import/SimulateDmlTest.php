<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Import;

use PhpMyAdmin\Core;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Import\SimulateDml;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Url;

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
        $this->dummyDbi->addResult($simulatedQuery, [[$expectedMatches]], ['COUNT(*)']);

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
                'SELECT COUNT(*)' .
                    ' FROM (SELECT 20 AS `n0`) AS `pma_new`' .
                    ' JOIN (SELECT `id` AS `o0` FROM `table_1` WHERE `id` > 10) AS `pma_old`' .
                    ' WHERE NOT (`n0`) <=> (`o0`)',
                2,
            ],
            'update statement_false_condition' => [
                'UPDATE `table_1` SET `id` = 20 WHERE 0',
                'SELECT COUNT(*)' .
                    ' FROM (SELECT 20 AS `n0`) AS `pma_new`' .
                    ' JOIN (SELECT `id` AS `o0` FROM `table_1` WHERE 0) AS `pma_old`' .
                    ' WHERE NOT (`n0`) <=> (`o0`)',
                0,
            ],
            'update statement_no_condition' => [
                'UPDATE `table_1` SET `id` = 20',
                'SELECT COUNT(*)' .
                    ' FROM (SELECT 20 AS `n0`) AS `pma_new`' .
                    ' JOIN (SELECT `id` AS `o0` FROM `table_1`) AS `pma_old`' .
                    ' WHERE NOT (`n0`) <=> (`o0`)',
                7,
            ],
            'update order by limit' => [
                'UPDATE `table_1` SET `id` = 20 ORDER BY `id` ASC LIMIT 3',
                'SELECT COUNT(*)' .
                    ' FROM (SELECT 20 AS `n0`) AS `pma_new`' .
                    ' JOIN (SELECT `id` AS `o0` FROM `table_1` ORDER BY `id` ASC LIMIT 3) AS `pma_old`' .
                    ' WHERE NOT (`n0`) <=> (`o0`)',
                3,
            ],
            'delete statement' => [
                'DELETE FROM `table_1` WHERE `id` > 10',
                'SELECT COUNT(*) FROM (SELECT 1 FROM `table_1` WHERE `id` > 10) AS `pma_tmp`',
                2,
            ],
            'delete statement_false_condition' => [
                'DELETE FROM `table_1` WHERE 0',
                'SELECT COUNT(*) FROM (SELECT 1 FROM `table_1` WHERE 0) AS `pma_tmp`',
                0,
            ],
            'delete statement order by limit' => [
                'DELETE FROM `table_1` ORDER BY `id` ASC LIMIT 3',
                'SELECT COUNT(*) FROM (SELECT 1 FROM `table_1` ORDER BY `id` ASC LIMIT 3) AS `pma_tmp`',
                2,
            ],
        ];
    }
}
