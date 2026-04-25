<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Table;

use PhpMyAdmin\Table\ColumnsDefinition;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Table\ColumnsDefinition
 */
class ColumnsDefinitionTest extends AbstractTestCase
{
    /**
     * test for ColumnsDefinition::decorateColumnMetaDefault
     *
     * @param array $columnMeta column metadata
     * @param array $expected   expected result
     * @phpstan-param array{Default: string|null, Null: 'YES'|'NO', Type: string} $columnMeta
     * @phpstan-param array{DefaultType: string, DefaultValue: string} $expected
     *
     * @dataProvider providerColumnMetaDefault
     */
    public function testDecorateColumnMetaDefault(array $columnMeta, array $expected): void
    {
        $result = ColumnsDefinition::decorateColumnMetaDefault($columnMeta);

        self::assertEquals($expected, $result);
    }

    /**
     * Data provider for testDecorateColumnMetaDefault
     *
     * @return array
     * @psalm-return array<string, array{
     *   array{Default: string|null, Null: 'YES'|'NO', Type: string},
     *   array{DefaultType: string, DefaultValue: string},
     * }>
     */
    public static function providerColumnMetaDefault(): array
    {
        return [
            'when Default is null and Null is YES' => [
                [
                    'Type' => 'int',
                    'Null' => 'YES',
                    'Default' => null,
                ],
                [
                    'DefaultType' => 'NULL',
                    'DefaultValue' => '',
                ],
            ],
            'when Default is null and Null is NO' => [
                [
                    'Type' => 'int',
                    'Null' => 'NO',
                    'Default' => null,
                ],
                [
                    'DefaultType' => 'NONE',
                    'DefaultValue' => '',
                ],
            ],
            'when Default is CURRENT_TIMESTAMP' => [
                [
                    'Type' => 'timestamp',
                    'Null' => 'NO',
                    'Default' => 'CURRENT_TIMESTAMP',
                ],
                [
                    'DefaultType' => 'CURRENT_TIMESTAMP',
                    'DefaultValue' => '',
                ],
            ],
            'when Default is current_timestamp' => [
                [
                    'Type' => 'datetime',
                    'Null' => 'NO',
                    'Default' => 'current_timestamp()',
                ],
                [
                    'DefaultType' => 'CURRENT_TIMESTAMP',
                    'DefaultValue' => '',
                ],
            ],
            'when Default is UUID' => [
                [
                    'Type' => 'UUID',
                    'Null' => 'NO',
                    'Default' => 'UUID',
                ],
                [
                    'DefaultType' => 'UUID',
                    'DefaultValue' => '',
                ],
            ],
            'when Default is uuid()' => [
                [
                    'Type' => 'UUID',
                    'Null' => 'YES',
                    'Default' => 'uuid()',
                ],
                [
                    'DefaultType' => 'UUID',
                    'DefaultValue' => '',
                ],
            ],
            'when Default is anything else and Type is text' => [
                [
                    'Type' => 'text',
                    'Null' => 'NO',
                    'Default' => "'some\\/thing'",
                ],
                [
                    'DefaultType' => 'USER_DEFINED',
                    'DefaultValue' => 'some/thing',
                ],
            ],
            'when Default is anything else and Type is not text' => [
                [
                    'Type' => 'something',
                    'Null' => 'NO',
                    'Default' => '"some\/thing"',
                ],
                [
                    'DefaultType' => 'USER_DEFINED',
                    'DefaultValue' => '"some\/thing"',
                ],
            ],
            'when varchar Default is empty string' => [
                [
                    'Type' => 'varchar(255)',
                    'Null' => 'YES',
                    'Default' => '',
                ],
                [
                    'DefaultType' => 'USER_DEFINED',
                    'DefaultValue' => '',
                ],
            ],
            'when longtext Default is empty string' => [
                [
                    'Type' => 'longtext',
                    'Null' => 'YES',
                    'Default' => "''",
                ],
                [
                    'DefaultType' => 'USER_DEFINED',
                    'DefaultValue' => '',
                ],
            ],
            'when text type default is an expression' => [
                [
                    'Type' => 'tinytext',
                    'Null' => 'YES',
                    'Default' => 'unix_timestamp()',
                ],
                [
                    'DefaultType' => 'USER_DEFINED',
                    'DefaultValue' => 'unix_timestamp()',
                ],
            ],
        ];
    }
}
