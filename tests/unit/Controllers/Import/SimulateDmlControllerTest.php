<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Import;

use PhpMyAdmin\Controllers\Import\SimulateDmlController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Import\SimulateDml;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Url;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use function count;
use function json_decode;

#[CoversClass(SimulateDmlController::class)]
final class SimulateDmlControllerTest extends AbstractTestCase
{
    /**
     * @psalm-param list<
     *   array{
     *     simulated: string,
     *     columns: list<non-empty-string>,
     *     result: list<non-empty-list<string|int|null>>,
     *   }
     * > $expectedPerQuery
     */
    #[DataProvider('providerForTestGetMatchedRows')]
    public function testGetMatchedRows(string $sqlQuery, array $expectedPerQuery): void
    {
        Current::$database = 'PMA';

        $dummyDbi = $this->createDbiDummy();
        foreach ($expectedPerQuery as $expected) {
            $dummyDbi->addSelectDb('PMA');
            $dummyDbi->addResult($expected['simulated'], $expected['result'], $expected['columns']);
        }

        $dbi = $this->createDatabaseInterface($dummyDbi);

        $controller = new SimulateDmlController(new ResponseRenderer(), new SimulateDml($dbi));
        /** @var Parser $parser */
        $parser = $this->callFunction($controller, SimulateDmlController::class, 'createParser', [$sqlQuery, ';']);
        self::assertCount(count($expectedPerQuery), $parser->statements);

        $this->callFunction($controller, SimulateDmlController::class, 'process', [$parser]);

        $dummyDbi->assertAllSelectsConsumed();
        $dummyDbi->assertAllQueriesConsumed();

        /** @var string $error */
        $error = $this->getProperty($controller, SimulateDmlController::class, 'error');
        self::assertSame('', $error);

        /** @var list<array<mixed>> $result */
        $result = $this->getProperty($controller, SimulateDmlController::class, 'data');

        foreach ($expectedPerQuery as $idx => $expectedData) {
            /** @var DeleteStatement|UpdateStatement $statement */
            $statement = $parser->statements[$idx];
            $expected = [
                'sql_query' => Generator::formatSql($statement->build()),
                'matched_rows' => count($expectedData['result']),
                'matched_rows_url' => Url::getFromRoute('/sql', [
                    'db' => 'PMA',
                    'sql_query' => $expectedData['simulated'],
                    'sql_signature' => Core::signSqlQuery($expectedData['simulated']),
                ]),
            ];

            self::assertEquals($expected, $result[$idx]);
        }
    }

    /**
     * @return array<
     *   array{
     *     string,
     *     list<array{
     *       simulated: string,
     *       columns: list<non-empty-string>,
     *       result: list<non-empty-list<string|int|null>>,
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
                        'simulated' =>
                            'SELECT * FROM (' .
                            'SELECT *, a AS `a ``new```, NULL AS `b ``new``` FROM `t` ORDER BY id DESC LIMIT 3' .
                            ') AS `pma_tmp`' .
                            ' WHERE NOT (`a`, `b`) <=> (`a ``new```, `b ``new```)',
                        'columns' => ['id', 'a', 'b', 'a `new`', 'b `new`'],
                        'result' => [[5, 2, 'test', 2, null]],
                    ],
                ],
            ],
            'update statement' => [
                'UPDATE `t` SET `a` = 20 WHERE `id` > 4',
                [
                    [
                        'simulated' =>
                            'SELECT *' .
                            ' FROM (SELECT *, 20 AS `a ``new``` FROM `t` WHERE `id` > 4) AS `pma_tmp`' .
                            ' WHERE NOT (`a`) <=> (`a ``new```)',
                        'columns' => ['id', 'a', 'b', 'a `new`'],
                        'result' => [
                            [5, 2, 'test', 20],
                            [6, 2, null, 20],
                        ],
                    ],
                ],
            ],
            'update statement false condition' => [
                'UPDATE `t` SET `a` = 20 WHERE 0',
                [
                    [
                        'simulated' =>
                            'SELECT *' .
                            ' FROM (SELECT *, 20 AS `a ``new``` FROM `t` WHERE 0) AS `pma_tmp`' .
                            ' WHERE NOT (`a`) <=> (`a ``new```)',
                        'columns' => ['id', 'a', 'b', 'a `new`'],
                        'result' => [],
                    ],
                ],
            ],
            'update statement no condition' => [
                'UPDATE `t` SET `a` = 2',
                [
                    [
                        'simulated' =>
                            'SELECT *' .
                            ' FROM (SELECT *, 2 AS `a ``new``` FROM `t`) AS `pma_tmp`' .
                            ' WHERE NOT (`a`) <=> (`a ``new```)',
                        'columns' => ['id', 'a', 'b', 'a `new`'],
                        'result' => [
                            [2, 1, null, 2],
                            [3, 1, null, 2],
                            [4, 1, null, 2],
                        ],
                    ],
                ],
            ],
            'update order by limit' => [
                'UPDATE `t` SET `id` = 20 ORDER BY `id` ASC LIMIT 3',
                [
                    [
                        'simulated' =>
                            'SELECT *' .
                            ' FROM (SELECT *, 20 AS `id ``new``` FROM `t` ORDER BY `id` ASC LIMIT 3) AS `pma_tmp`' .
                            ' WHERE NOT (`id`) <=> (`id ``new```)',
                        'columns' => ['id', 'a', 'b', 'id `new`'],
                        'result' => [
                            [1, 2, 'test', 20],
                            [2, 1, null, 20],
                            [3, 1, null, 20],
                        ],
                    ],
                ],
            ],
            'update duplicate set' => [
                'UPDATE `t` SET `id` = 2, `id` = 1 WHERE `id` = 1',
                [
                    [
                        'simulated' =>
                            'SELECT *' .
                            ' FROM (SELECT *, 1 AS `id ``new``` FROM `t` WHERE `id` = 1) AS `pma_tmp`' .
                            ' WHERE NOT (`id`) <=> (`id ``new```)',
                        'columns' => ['id', 'a', 'b', 'id `new`'],
                        'result' => [],
                    ],
                ],
            ],
            'delete statement' => [
                'DELETE FROM `t` WHERE `id` > 4',
                [
                    [
                        'simulated' => 'SELECT * FROM `t` WHERE `id` > 4',
                        'columns' => ['id', 'a', 'b'],
                        'result' => [
                            [5, 2, 'test'],
                            [6, 2, null],
                        ],
                    ],
                ],
            ],
            'delete statement false condition' => [
                'DELETE FROM `t` WHERE 0',
                [
                    [
                        'simulated' => 'SELECT * FROM `t` WHERE 0',
                        'columns' => ['id', 'a', 'b'],
                        'result' => [],
                    ],
                ],
            ],
            'delete statement order by limit' => [
                'DELETE FROM `t` ORDER BY `id` ASC LIMIT 3',
                [
                    [
                        'simulated' => 'SELECT * FROM `t` ORDER BY `id` ASC LIMIT 3',
                        'columns' => ['id', 'a', 'b'],
                        'result' => [
                            [1, 2, 'test'],
                            [2, 1, null],
                            [3, 1, null],
                        ],
                    ],
                ],
            ],
            'multiple statements' => [
                'UPDATE `t` SET `b` = `a`; DELETE FROM `t` WHERE 1',
                [
                    [
                        'simulated' =>
                            'SELECT *' .
                            ' FROM (SELECT *, `a` AS `b ``new``` FROM `t`) AS `pma_tmp`' .
                            ' WHERE NOT (`b`) <=> (`b ``new```)',
                        'columns' => ['id', 'a', 'b', 'b `new`'],
                        'result' => [
                            [1, 2, 2, 'test'],
                            [2, 1, 1, null],
                            [3, 1, 1, null],
                            [4, 1, 1, null],
                            [5, 2, 2, 'test'],
                            [6, 2, 2, null],
                        ],
                    ],
                    [
                        'simulated' => 'SELECT * FROM `t` WHERE 1',
                        'columns' => ['id', 'a', 'b'],
                        'result' => [
                            [1, 2, 'test'],
                            [2, 1, null],
                            [3, 1, null],
                            [4, 1, null],
                            [5, 2, 'test'],
                            [6, 2, null],
                        ],
                    ],
                ],
            ],
            'statement with comment' => [
                "UPDATE `t` SET `a` = 20 -- oops\nWHERE 0",
                [
                    [
                        'simulated' =>
                            'SELECT *' .
                            ' FROM (SELECT *, 20 AS `a ``new``` FROM `t` WHERE 0) AS `pma_tmp`' .
                            ' WHERE NOT (`a`) <=> (`a ``new```)',
                        'columns' => ['id', 'a', 'b', 'a `new`'],
                        'result' => [],
                    ],
                ],
            ],
        ];
    }

    public function testStatementWithParsingError(): void
    {
        Current::$sqlQuery = 'UPDATE actor SET';

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['sql_delimiter' => ';']);

        $responseRenderer = new ResponseRenderer();
        $responseRenderer->setAjax(true);
        $controller = new SimulateDmlController($responseRenderer, new SimulateDml($this->createDatabaseInterface()));
        $response = $controller($request);

        $expectedMessage = <<<'HTML'
            <div class="alert alert-danger" role="alert">
              <img src="themes/dot.gif" title="" alt="" class="icon ic_s_error"> Missing assignment in SET operation.
            </div>

            HTML;

        $body = (string) $response->getBody();
        self::assertJson($body);
        self::assertSame(
            ['message' => $expectedMessage, 'sql_data' => false, 'success' => true],
            json_decode($body, true),
        );
    }
}
