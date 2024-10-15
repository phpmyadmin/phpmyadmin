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
     * @phpstan-param array<string, string|null> $columnMeta
     * @phpstan-param array<string, string> $expected
     *
     * @dataProvider providerColumnMetaDefault
     */
    public function testDecorateColumnMetaDefault(array $columnMeta, array $expected): void
    {
        $result = ColumnsDefinition::decorateColumnMetaDefault($columnMeta);

        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for testDecorateColumnMetaDefault
     *
     * @return array
     * @psalm-return array<string, array{array<string, string|null>, array<string, string>}>
     */
    public static function providerColumnMetaDefault(): array
    {
        return [
            'when Default is null and Null is YES' => [
                [
                    'Default' => null,
                    'Null' => 'YES',
                ],
                [
                    'DefaultType' => 'NULL',
                    'DefaultValue' => '',
                ],
            ],
            'when Default is null and Null is NO' => [
                [
                    'Default' => null,
                    'Null' => 'NO',
                ],
                [
                    'DefaultType' => 'NONE',
                    'DefaultValue' => '',
                ],
            ],
            'when Default is CURRENT_TIMESTAMP' => [
                ['Default' => 'CURRENT_TIMESTAMP'],
                [
                    'DefaultType' => 'CURRENT_TIMESTAMP',
                    'DefaultValue' => '',
                ],
            ],
            'when Default is current_timestamp' => [
                ['Default' => 'current_timestamp()'],
                [
                    'DefaultType' => 'CURRENT_TIMESTAMP',
                    'DefaultValue' => '',
                ],
            ],
            'when Default is UUID' => [
                ['Default' => 'UUID'],
                [
                    'DefaultType' => 'UUID',
                    'DefaultValue' => '',
                ],
            ],
            'when Default is uuid()' => [
                ['Default' => 'uuid()'],
                [
                    'DefaultType' => 'UUID',
                    'DefaultValue' => '',
                ],
            ],
            'when Default is anything else and Type is text' => [
                [
                    'Default' => '"some\/thing"',
                    'Type' => 'text',
                ],
                [
                    'Default' => 'some/thing',
                    'DefaultType' => 'USER_DEFINED',
                    'DefaultValue' => '"some\/thing"',
                ],
            ],
            'when Default is anything else and Type is not text' => [
                [
                    'Default' => '"some\/thing"',
                    'Type' => 'something',
                ],
                [
                    'DefaultType' => 'USER_DEFINED',
                    'DefaultValue' => '"some\/thing"',
                ],
            ],
        ];
    }
}
