<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\UniqueCondition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use function str_repeat;

use const MYSQLI_NUM_FLAG;
use const MYSQLI_PRI_KEY_FLAG;
use const MYSQLI_TYPE_BIT;
use const MYSQLI_TYPE_GEOMETRY;
use const MYSQLI_TYPE_LONG;
use const MYSQLI_TYPE_SHORT;
use const MYSQLI_TYPE_STRING;
use const MYSQLI_TYPE_TINY;
use const MYSQLI_TYPE_VAR_STRING;
use const MYSQLI_UNIQUE_KEY_FLAG;

#[CoversClass(UniqueCondition::class)]
class UniqueConditionTest extends AbstractTestCase
{
    public function testGetUniqueCondition(): void
    {
        Current::$database = 'db';
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $actual = new UniqueCondition([], []);
        self::assertSame(['', false, []], [
            $actual->getWhereClause(),
            $actual->isClauseUnique(),
            $actual->getConditionArray(),
        ]);

        $actual = new UniqueCondition([], [], true);
        self::assertSame(['', true, []], [
            $actual->getWhereClause(),
            $actual->isClauseUnique(),
            $actual->getConditionArray(),
        ]);
    }

    public function testGetUniqueConditionWithMultipleFields(): void
    {
        DatabaseInterface::$instance = $this->createDatabaseInterface();

        $meta = [
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_STRING,
                'name' => 'field1',
                'table' => 'table',
                'orgtable' => 'table',
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_STRING,
                'name' => 'field2',
                'table' => 'table',
                'orgtable' => 'table',
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_SHORT,
                'flags' => MYSQLI_NUM_FLAG,
                'name' => 'field3',
                'table' => 'table',
                'orgtable' => 'table',
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_LONG,
                'flags' => MYSQLI_NUM_FLAG,
                'name' => 'field4',
                'table' => 'table',
                'orgtable' => 'table',
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_STRING,
                'name' => 'field5',
                'table' => 'table',
                'orgtable' => 'table',
                'charsetnr' => 63, // binary
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_STRING,
                'name' => 'field6',
                'table' => 'table',
                'orgtable' => 'table',
                'charsetnr' => 63, // binary
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_STRING,
                'name' => 'field7',
                'table' => 'table',
                'orgtable' => 'table',
                'charsetnr' => 32, // armscii8_general_ci
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_STRING,
                'name' => 'field8',
                'table' => 'table',
                'orgtable' => 'table',
                'charsetnr' => 48, // latin1_general_ci
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_STRING,
                'name' => 'field9',
                'table' => 'table',
                'orgtable' => 'table',
                'charsetnr' => 63, // binary
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_GEOMETRY,
                'name' => 'field10',
                'table' => 'table',
                'orgtable' => 'table',
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_STRING,
                'name' => 'field11',
                'table' => 'table2',
                'orgtable' => 'table2',
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_BIT,
                'name' => 'field12',
                'table' => 'table',
                'orgtable' => 'table',
                'length' => 4,
            ]),
        ];

        $actual = new UniqueCondition($meta, [
            null,
            'value\'s',
            123456,
            123.456,
            'value',
            str_repeat('*', 1001),
            'value',
            'value',
            'value',
            'value',
            'value',
            0x1,
        ], false, 'table');
        self::assertSame(
            [
                '`table`.`field1` IS NULL AND `table`.`field2` = \'value\\\'s\' AND `table`.`field3` = 123456'
                . ' AND `table`.`field4` = 123.456 AND `table`.`field5` = CAST(0x76616c7565 AS BINARY)'
                . ' AND `table`.`field7` = \'value\' AND `table`.`field8` = \'value\''
                . ' AND `table`.`field9` = CAST(0x76616c7565 AS BINARY)'
                . ' AND `table`.`field10` = CAST(0x76616c7565 AS BINARY)'
                . ' AND `table`.`field12` = b\'0001\'',
                false,
                [
                    '`table`.`field1`' => 'IS NULL',
                    '`table`.`field2`' => '= \'value\\\'s\'',
                    '`table`.`field3`' => '= 123456',
                    '`table`.`field4`' => '= 123.456',
                    '`table`.`field5`' => '= CAST(0x76616c7565 AS BINARY)',
                    '`table`.`field7`' => '= \'value\'',
                    '`table`.`field8`' => '= \'value\'',
                    '`table`.`field9`' => '= CAST(0x76616c7565 AS BINARY)',
                    '`table`.`field10`' => '',
                    '`table`.`field12`' => '= b\'0001\'',
                ],
            ],
            [$actual->getWhereClause(), $actual->isClauseUnique(), $actual->getConditionArray()],
        );
    }

    public function testGetUniqueConditionWithSingleBigBinaryField(): void
    {
        $meta = [
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_STRING,
                'name' => 'field',
                'table' => 'table',
                'orgtable' => 'table',
                'charsetnr' => 63, // binary
            ]),
        ];

        $actual = new UniqueCondition($meta, [str_repeat('*', 1001)]);
        self::assertSame(
            ['CHAR_LENGTH(`table`.`field`)  = 1001', false, ['`table`.`field`' => ' = 1001']],
            [$actual->getWhereClause(), $actual->isClauseUnique(), $actual->getConditionArray()],
        );
    }

    public function testGetUniqueConditionWithPrimaryKey(): void
    {
        DatabaseInterface::$instance = $this->createDatabaseInterface();

        $meta = [
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_LONG,
                'flags' => MYSQLI_PRI_KEY_FLAG | MYSQLI_NUM_FLAG,
                'name' => 'id',
                'table' => 'table',
                'orgtable' => 'table',
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_STRING,
                'name' => 'field',
                'table' => 'table',
                'orgtable' => 'table',
            ]),
        ];

        $actual = new UniqueCondition($meta, [1, 'value']);
        self::assertSame(['`table`.`id` = 1', true, ['`table`.`id`' => '= 1']], [
            $actual->getWhereClause(),
            $actual->isClauseUnique(),
            $actual->getConditionArray(),
        ]);
    }

    public function testGetUniqueConditionWithUniqueKey(): void
    {
        DatabaseInterface::$instance = $this->createDatabaseInterface();

        $meta = [
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_STRING,
                'flags' => MYSQLI_UNIQUE_KEY_FLAG,
                'name' => 'id',
                'table' => 'table',
                'orgtable' => 'table',
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_STRING,
                'name' => 'field',
                'table' => 'table',
                'orgtable' => 'table',
            ]),
        ];

        $actual = new UniqueCondition($meta, ['unique', 'value']);
        self::assertSame(['`table`.`id` = \'unique\'', true, ['`table`.`id`' => '= \'unique\'']], [
            $actual->getWhereClause(),
            $actual->isClauseUnique(),
            $actual->getConditionArray(),
        ]);
    }

    /**
     * Test for new UniqueCondition
     * note: GROUP_FLAG = MYSQLI_NUM_FLAG = 32769
     *
     * @param FieldMetadata[]                         $meta     Meta Information for Field
     * @param int[]|string[]                          $row      Current Ddata Row
     * @param array<string, string>[]|string[]|bool[] $expected Expected Result
     * @psalm-param array<int, string|int|float|null> $row
     * @psalm-param array{string, bool, array<string, string>} $expected
     */
    #[DataProvider('providerGetUniqueConditionForGroupFlag')]
    public function testGetUniqueConditionForGroupFlag(array $meta, array $row, array $expected): void
    {
        DatabaseInterface::$instance = $this->createDatabaseInterface();

        $actual = new UniqueCondition($meta, $row);

        self::assertSame($expected, [
            $actual->getWhereClause(),
            $actual->isClauseUnique(),
            $actual->getConditionArray(),
        ]);
    }

    /**
     * Provider for testGetUniqueConditionForGroupFlag
     *
     * @return array<string, array{
     *  FieldMetadata[],
     *  array<int,
     *  string|int|float|null>,
     *  array{string, bool, array<string, string>}
     * }>
     */
    public static function providerGetUniqueConditionForGroupFlag(): array
    {
        return [
            'field type is integer, value is number - not escape string' => [
                [
                    FieldHelper::fromArray([
                        'type' => MYSQLI_TYPE_TINY,
                        'flags' => MYSQLI_NUM_FLAG,
                        'name' => 'col',
                        'table' => 'table',
                        'orgtable' => 'table',
                    ]),
                ],
                [123],
                ['`table`.`col` = 123', false, ['`table`.`col`' => '= 123']],
            ],
            'field type is unknown, value is string - escape string' => [
                [
                    FieldHelper::fromArray([
                        'type' => -1,
                        'flags' => MYSQLI_NUM_FLAG,
                        'name' => 'col',
                        'table' => 'table',
                        'orgtable' => 'table',
                    ]),
                ],
                ['test'],
                ["`table`.`col` = 'test'", false, ['`table`.`col`' => "= 'test'"]],
            ],
            'field type is varchar, value is string - escape string' => [
                [
                    FieldHelper::fromArray([
                        'type' => MYSQLI_TYPE_VAR_STRING,
                        'flags' => MYSQLI_NUM_FLAG,
                        'name' => 'col',
                        'table' => 'table',
                        'orgtable' => 'table',
                    ]),
                ],
                ['test'],
                ["`table`.`col` = 'test'", false, ['`table`.`col`' => "= 'test'"]],
            ],
            'field type is varchar, value is string with double quote - escape string' => [
                [
                    FieldHelper::fromArray([
                        'type' => MYSQLI_TYPE_VAR_STRING,
                        'flags' => MYSQLI_NUM_FLAG,
                        'name' => 'col',
                        'table' => 'table',
                        'orgtable' => 'table',
                    ]),
                ],
                ['"test"'],
                ["`table`.`col` = '\\\"test\\\"'", false, ['`table`.`col`' => "= '\\\"test\\\"'"]],
            ],
            'field type is varchar, value is string with single quote - escape string' => [
                [
                    FieldHelper::fromArray([
                        'type' => MYSQLI_TYPE_VAR_STRING,
                        'flags' => MYSQLI_NUM_FLAG,
                        'name' => 'col',
                        'table' => 'table',
                        'orgtable' => 'table',
                    ]),
                ],
                ["'test'"],
                ["`table`.`col` = '\'test\''", false, ['`table`.`col`' => "= '\'test\''"]],
            ],
            'group by multiple columns and field type is mixed' => [
                [
                    FieldHelper::fromArray([
                        'type' => MYSQLI_TYPE_VAR_STRING,
                        'flags' => MYSQLI_NUM_FLAG,
                        'name' => 'col',
                        'table' => 'table',
                        'orgtable' => 'table',
                    ]),
                    FieldHelper::fromArray([
                        'type' => MYSQLI_TYPE_TINY,
                        'flags' => MYSQLI_NUM_FLAG,
                        'name' => 'status_id',
                        'table' => 'table',
                        'orgtable' => 'table',
                    ]),
                ],
                ['test', 2],
                [
                    "`table`.`col` = 'test' AND `table`.`status_id` = 2",
                    false,
                    ['`table`.`col`' => "= 'test'", '`table`.`status_id`' => '= 2'],
                ],
            ],
        ];
    }
}
