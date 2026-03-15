<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Import;

use PhpMyAdmin\Controllers\Import\SimulateDmlController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Import\SimulateDml;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Url;

use function count;

/**
 * @covers \PhpMyAdmin\Controllers\Import\SimulateDmlController
 */
class SimulateDmlControllerTest extends AbstractTestCase
{
    /**
     * @param array<array<mixed>> $expectedPerQuery
     * @psalm-param list<
     *   array{
     *     count: string,
     *     select: string,
     *     affected_rows: int,
     *   }
     * > $expectedPerQuery
     *
     * @dataProvider providerForTestGetMatchedRows
     */
    public function testGetMatchedRows(string $sqlQuery, array $expectedPerQuery): void
    {
        $GLOBALS['db'] = 'PMA';

        foreach ($expectedPerQuery as $expected) {
            $this->dummyDbi->addSelectDb('PMA');
            $this->dummyDbi->addResult($expected['count'], [[$expected['affected_rows']]]);
        }

        $controller = new SimulateDmlController(
            new ResponseRenderer(),
            new Template(),
            new SimulateDml($this->dbi)
        );
        /** @var Parser $parser */
        $parser = $this->callFunction($controller, SimulateDmlController::class, 'createParser', [$sqlQuery, ';']);
        self::assertCount(count($expectedPerQuery), $parser->statements);

        $this->callFunction($controller, SimulateDmlController::class, 'process', [$parser]);

        $this->assertAllSelectsConsumed();
        $this->assertAllQueriesConsumed();

        /** @var string $error */
        $error = $this->getProperty($controller, SimulateDmlController::class, 'error');
        self::assertSame('', $error);

        /** @var list<array<mixed>> $result */
        $result = $this->getProperty($controller, SimulateDmlController::class, 'data');

        foreach ($expectedPerQuery as $idx => $expectedData) {
            /** @var DeleteStatement|UpdateStatement $statement */
            $statement = $parser->statements[$idx];
            $selectQuery = $expectedData['select'];
            $expected = [
                'sql_query' => Generator::formatSql($statement->build()),
                'matched_rows' => $expectedData['affected_rows'],
                'matched_rows_url' => Url::getFromRoute('/sql', [
                    'db' => 'PMA',
                    'sql_query' => $selectQuery,
                    'sql_signature' => Core::signSqlQuery($selectQuery),
                ]),
            ];

            self::assertSame($expected, $result[$idx]);
        }
    }

    /**
     * @return array<string, array<mixed>>
     * @psalm-return array<
     *   array{
     *     string,
     *     list<array{
     *       count: string,
     *       select: string,
     *       affected_rows: int,
     *     }>
     *   }
     * >
     */
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
                [
                    [
                        'count' =>
                            'SELECT COUNT(*) FROM (' .
                            'SELECT `a`, `b`, a AS `a *new*`, NULL AS `b *new*` FROM `t` ORDER BY id DESC LIMIT 3' .
                            ') AS `pma_tmp`' .
                            ' WHERE NOT (`a`, `b`) <=> (`a *new*`, `b *new*`)',
                        'select' =>
                            'SELECT * FROM (' .
                            'SELECT *, a AS `a *new*`, NULL AS `b *new*` FROM `t` ORDER BY id DESC LIMIT 3' .
                            ') AS `pma_tmp`' .
                            ' WHERE NOT (`a`, `b`) <=> (`a *new*`, `b *new*`)',
                        'affected_rows' => 1,
                    ],
                ],
            ],
            'update statement' => [
                'UPDATE `t` SET `a` = 20 WHERE `id` > 4',
                [
                    [
                        'count' =>
                            'SELECT COUNT(*)' .
                            ' FROM (SELECT `a`, 20 AS `a *new*` FROM `t` WHERE `id` > 4) AS `pma_tmp`' .
                            ' WHERE NOT (`a`) <=> (`a *new*`)',
                        'select' =>
                            'SELECT *' .
                            ' FROM (SELECT *, 20 AS `a *new*` FROM `t` WHERE `id` > 4) AS `pma_tmp`' .
                            ' WHERE NOT (`a`) <=> (`a *new*`)',
                        'affected_rows' => 2,
                    ],
                ],
            ],
            'update statement false condition' => [
                'UPDATE `t` SET `a` = 20 WHERE 0',
                [
                    [
                        'count' =>
                            'SELECT COUNT(*)' .
                            ' FROM (SELECT `a`, 20 AS `a *new*` FROM `t` WHERE 0) AS `pma_tmp`' .
                            ' WHERE NOT (`a`) <=> (`a *new*`)',
                        'select' =>
                            'SELECT *' .
                            ' FROM (SELECT *, 20 AS `a *new*` FROM `t` WHERE 0) AS `pma_tmp`' .
                            ' WHERE NOT (`a`) <=> (`a *new*`)',
                        'affected_rows' => 0,
                    ],
                ],
            ],
            'update statement no condition' => [
                'UPDATE `t` SET `a` = 2',
                [
                    [
                        'count' =>
                            'SELECT COUNT(*)' .
                            ' FROM (SELECT `a`, 2 AS `a *new*` FROM `t`) AS `pma_tmp`' .
                            ' WHERE NOT (`a`) <=> (`a *new*`)',
                        'select' =>
                            'SELECT *' .
                            ' FROM (SELECT *, 2 AS `a *new*` FROM `t`) AS `pma_tmp`' .
                            ' WHERE NOT (`a`) <=> (`a *new*`)',
                        'affected_rows' => 3,
                    ],
                ],
            ],
            'update order by limit' => [
                'UPDATE `t` SET `id` = 20 ORDER BY `id` ASC LIMIT 3',
                [
                    [
                        'count' =>
                            'SELECT COUNT(*)' .
                            ' FROM (SELECT `id`, 20 AS `id *new*` FROM `t` ORDER BY `id` ASC LIMIT 3) AS `pma_tmp`' .
                            ' WHERE NOT (`id`) <=> (`id *new*`)',
                        'select' =>
                            'SELECT *' .
                            ' FROM (SELECT *, 20 AS `id *new*` FROM `t` ORDER BY `id` ASC LIMIT 3) AS `pma_tmp`' .
                            ' WHERE NOT (`id`) <=> (`id *new*`)',
                        'affected_rows' => 3,
                    ],
                ],
            ],
            'update duplicate set' => [
                'UPDATE `t` SET `id` = 2, `id` = 1 WHERE `id` = 1',
                [
                    [
                        'count' =>
                            'SELECT COUNT(*)' .
                            ' FROM (SELECT `id`, 1 AS `id *new*` FROM `t` WHERE `id` = 1) AS `pma_tmp`' .
                            ' WHERE NOT (`id`) <=> (`id *new*`)',
                        'select' =>
                            'SELECT *' .
                            ' FROM (SELECT *, 1 AS `id *new*` FROM `t` WHERE `id` = 1) AS `pma_tmp`' .
                            ' WHERE NOT (`id`) <=> (`id *new*`)',
                        'affected_rows' => 0,
                    ],
                ],
            ],
            'delete statement' => [
                'DELETE FROM `t` WHERE `id` > 4',
                [
                    [
                        'count' => 'SELECT COUNT(*) FROM (SELECT 1 FROM `t` WHERE `id` > 4) AS pma_tmp',
                        'select' => 'SELECT * FROM (SELECT * FROM `t` WHERE `id` > 4) AS pma_tmp',
                        'affected_rows' => 2,
                    ],
                ],
            ],
            'delete statement false condition' => [
                'DELETE FROM `t` WHERE 0',
                [
                    [
                        'count' => 'SELECT COUNT(*) FROM (SELECT 1 FROM `t` WHERE 0) AS pma_tmp',
                        'select' => 'SELECT * FROM (SELECT * FROM `t` WHERE 0) AS pma_tmp',
                        'affected_rows' => 0,
                    ],
                ],
            ],
            'delete statement order by limit' => [
                'DELETE FROM `t` ORDER BY `id` ASC LIMIT 3',
                [
                    [
                        'count' => 'SELECT COUNT(*) FROM (SELECT 1 FROM `t` ORDER BY `id` ASC LIMIT 3) AS pma_tmp',
                        'select' => 'SELECT * FROM (SELECT * FROM `t` ORDER BY `id` ASC LIMIT 3) AS pma_tmp',
                        'affected_rows' => 3,
                    ],
                ],
            ],
            'multiple statments' => [
                'UPDATE `t` SET `b` = `a`; DELETE FROM `t` WHERE 1',
                [
                    [
                        'count' =>
                            'SELECT COUNT(*)' .
                            ' FROM (SELECT `b`, `a` AS `b *new*` FROM `t`) AS `pma_tmp`' .
                            ' WHERE NOT (`b`) <=> (`b *new*`)',
                        'select' =>
                            'SELECT *' .
                            ' FROM (SELECT *, `a` AS `b *new*` FROM `t`) AS `pma_tmp`' .
                            ' WHERE NOT (`b`) <=> (`b *new*`)',
                        'affected_rows' => 6,
                    ],
                    [
                        'count' => 'SELECT COUNT(*) FROM (SELECT 1 FROM `t` WHERE 1) AS pma_tmp',
                        'select' => 'SELECT * FROM (SELECT * FROM `t` WHERE 1) AS pma_tmp',
                        'affected_rows' => 6,
                    ],
                ],
            ],
            'statement with comment' => [
                "UPDATE `t` SET `a` = 20 -- oops\nWHERE 0",
                [
                    [
                        'count' =>
                            'SELECT COUNT(*)' .
                            ' FROM (SELECT `a`, 20 AS `a *new*` FROM `t` WHERE 0) AS `pma_tmp`' .
                            ' WHERE NOT (`a`) <=> (`a *new*`)',
                        'select' =>
                            'SELECT *' .
                            ' FROM (SELECT *, 20 AS `a *new*` FROM `t` WHERE 0) AS `pma_tmp`' .
                            ' WHERE NOT (`a`) <=> (`a *new*`)',
                        'affected_rows' => 0,
                    ],
                ],
            ],
        ];
    }

    public function testStatementWithParsingError(): void
    {
        $_POST['sql_delimiter'] = ';';
        $GLOBALS['sql_query'] = 'UPDATE actor SET';

        $responseRenderer = new ResponseRenderer();
        $controller = new SimulateDmlController(
            $responseRenderer,
            new Template(),
            new SimulateDml($this->createDatabaseInterface())
        );
        $controller();

        $expectedMessage = <<<'HTML'
<div class="alert alert-danger" role="alert">
  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_error"> Missing assignment in SET operation.
</div>

HTML;

        self::assertSame(['message' => $expectedMessage, 'sql_data' => false], $responseRenderer->getJSONResult());
    }
}
