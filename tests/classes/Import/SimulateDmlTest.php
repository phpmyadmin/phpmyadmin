<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Import;

use PhpMyAdmin\Core;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Import\SimulateDml;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Url;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use function count;

#[CoversClass(SimulateDml::class)]
class SimulateDmlTest extends AbstractTestCase
{
    /**
     * @param list<non-empty-string>                $columns
     * @param list<non-empty-list<string|int|null>> $result
     */
    #[DataProvider('providerForTestGetMatchedRows')]
    public function testGetMatchedRows(string $sqlQuery, string $simulatedQuery, array $columns, array $result): void
    {
        $GLOBALS['db'] = 'PMA';
        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('PMA');
        $dummyDbi->addResult($simulatedQuery, $result, $columns);
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $object = new SimulateDml($dbi);
        $parser = new Parser($sqlQuery);

        /** @var DeleteStatement|UpdateStatement $statement */
        $statement = $parser->statements[0];
        $simulatedData = $object->getMatchedRows($sqlQuery, $parser, $statement);

        $matchedRowsUrl = Url::getFromRoute('/sql', [
            'db' => 'PMA',
            'sql_query' => $simulatedQuery,
            'sql_signature' => Core::signSqlQuery($simulatedQuery),
        ]);

        $dummyDbi->assertAllSelectsConsumed();
        $dummyDbi->assertAllQueriesConsumed();
        $this->assertEquals([
            'sql_query' => Generator::formatSql($sqlQuery),
            'matched_rows' => count($result),
            'matched_rows_url' => $matchedRowsUrl,
        ], $simulatedData);
    }

    /** @return array<string, array{string, string, list<non-empty-string>, list<non-empty-list<string|int|null>>}> */
    public static function providerForTestGetMatchedRows(): array
    {
        // Data from table:
        // CREATE TABLE `t` AS
        // SELECT 1 AS `id`, 2 AS `a`, 'test' AS `b` UNION ALL
        // SELECT 2 AS `id`, 1 AS `a`,  NULL  AS `b` UNION ALL
        // SELECT 3 AS `id`, 1 AS `a`,  NULL  AS `b` UNION ALL
        // SELECT 4 AS `id`, 1 AS `a`,  NULL  AS `b` UNION ALL
        // SELECT 5 AS `id`, 2 AS `a`, 'test' AS `b` UNION ALL
        // SELECT 6 AS `id`, 2 AS `a`,  NULL  AS `b`
        return [
            'update statement set null' => [
                'UPDATE t SET `b` = NULL, a = a ORDER BY id DESC LIMIT 3',
                'SELECT * FROM (' .
                    'SELECT *, a AS `a ``new```, NULL AS `b ``new``` FROM `t` ORDER BY id DESC LIMIT 3' .
                    ') AS `pma_tmp`' .
                    ' WHERE NOT (`a`, `b`) <=> (`a ``new```, `b ``new```)',
                ['id', 'a', 'b', 'a `new`', 'b `new`'],
                [[5, 2, 'test', 2, null]],
            ],
            'update statement' => [
                'UPDATE `t` SET `a` = 20 WHERE `id` > 4',
                'SELECT *' .
                    ' FROM (SELECT *, 20 AS `a ``new``` FROM `t` WHERE `id` > 4) AS `pma_tmp`' .
                    ' WHERE NOT (`a`) <=> (`a ``new```)',
                ['id', 'a', 'b', 'a `new`'],
                [
                    [5, 2, 'test', 20],
                    [6, 2, null, 20],
                ],
            ],
            'update statement false condition' => [
                'UPDATE `t` SET `a` = 20 WHERE 0',
                'SELECT *' .
                    ' FROM (SELECT *, 20 AS `a ``new``` FROM `t` WHERE 0) AS `pma_tmp`' .
                    ' WHERE NOT (`a`) <=> (`a ``new```)',
                ['id', 'a', 'b', 'a `new`'],
                [],
            ],
            'update statement no condition' => [
                'UPDATE `t` SET `a` = 2',
                'SELECT *' .
                    ' FROM (SELECT *, 2 AS `a ``new``` FROM `t`) AS `pma_tmp`' .
                    ' WHERE NOT (`a`) <=> (`a ``new```)',
                ['id', 'a', 'b', 'a `new`'],
                [
                    [2, 1, null, 2],
                    [3, 1, null, 2],
                    [4, 1, null, 2],
                ],
            ],
            'update order by limit' => [
                'UPDATE `t` SET `id` = 20 ORDER BY `id` ASC LIMIT 3',
                'SELECT *' .
                    ' FROM (SELECT *, 20 AS `id ``new``` FROM `t` ORDER BY `id` ASC LIMIT 3) AS `pma_tmp`' .
                    ' WHERE NOT (`id`) <=> (`id ``new```)',
                ['id', 'a', 'b', 'id `new`'],
                [
                    [1, 2, 'test', 20],
                    [2, 1, null, 20],
                    [3, 1, null, 20],
                ],
            ],
            'update duplicate set' => [
                'UPDATE `t` SET `id` = 2, `id` = 1 WHERE `id` = 1',
                'SELECT *' .
                    ' FROM (SELECT *, 1 AS `id ``new``` FROM `t` WHERE `id` = 1) AS `pma_tmp`' .
                    ' WHERE NOT (`id`) <=> (`id ``new```)',
                ['id', 'a', 'b', 'id `new`'],
                [],
            ],
            'delete statement' => [
                'DELETE FROM `t` WHERE `id` > 4',
                'SELECT * FROM `t` WHERE `id` > 4',
                ['id', 'a', 'b'],
                [
                    [5, 2, 'test'],
                    [6, 2, null],
                ],
            ],
            'delete statement false condition' => [
                'DELETE FROM `t` WHERE 0',
                'SELECT * FROM `t` WHERE 0',
                ['id', 'a', 'b'],
                [],
            ],
            'delete statement order by limit' => [
                'DELETE FROM `t` ORDER BY `id` ASC LIMIT 3',
                'SELECT * FROM `t` ORDER BY `id` ASC LIMIT 3',
                ['id', 'a', 'b'],
                [
                    [1, 2, 'test'],
                    [2, 1, null],
                    [3, 1, null],
                ],
            ],
        ];
    }
}
