<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Settings;

use PhpMyAdmin\Config\Settings\Import;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_merge;

/**
 * @covers \PhpMyAdmin\Config\Settings\Import
 */
class ImportTest extends TestCase
{
    /** @var array<string, bool|int|string> */
    private $defaultValues = [
        'format' => 'sql',
        'charset' => '',
        'allow_interrupt' => true,
        'skip_queries' => 0,
        'sql_compatibility' => 'NONE',
        'sql_no_auto_value_on_zero' => true,
        'sql_read_as_multibytes' => false,
        'csv_replace' => false,
        'csv_ignore' => false,
        'csv_terminated' => ',',
        'csv_enclosed' => '"',
        'csv_escaped' => '"',
        'csv_new_line' => 'auto',
        'csv_columns' => '',
        'csv_col_names' => false,
        'ldi_replace' => false,
        'ldi_ignore' => false,
        'ldi_terminated' => ';',
        'ldi_enclosed' => '"',
        'ldi_escaped' => '\\',
        'ldi_new_line' => 'auto',
        'ldi_columns' => '',
        'ldi_local_option' => 'auto',
        'ods_col_names' => false,
        'ods_empty_rows' => true,
        'ods_recognize_percentages' => true,
        'ods_recognize_currency' => true,
    ];

    /**
     * @param mixed[][] $values
     * @psalm-param (array{0: string, 1: mixed, 2: mixed})[] $values
     *
     * @dataProvider providerForTestConstructor
     */
    public function testConstructor(array $values): void
    {
        $actualValues = [];
        $expectedValues = [];
        /** @psalm-suppress MixedAssignment */
        foreach ($values as $value) {
            $actualValues[$value[0]] = $value[1];
            $expectedValues[$value[0]] = $value[2];
        }

        $expected = array_merge($this->defaultValues, $expectedValues);
        $settings = new Import($actualValues);

        foreach (array_keys($expectedValues) as $key) {
            $this->assertSame($expected[$key], $settings->$key);
        }
    }

    /**
     * [setting key, actual value, expected value]
     *
     * @return mixed[][][][]
     * @psalm-return (array{0: string, 1: mixed, 2: mixed})[][][]
     */
    public function providerForTestConstructor(): array
    {
        return [
            'null values' => [
                [
                    ['format', null, 'sql'],
                    ['charset', null, ''],
                    ['allow_interrupt', null, true],
                    ['skip_queries', null, 0],
                    ['sql_compatibility', null, 'NONE'],
                    ['sql_no_auto_value_on_zero', null, true],
                    ['sql_read_as_multibytes', null, false],
                    ['csv_replace', null, false],
                    ['csv_ignore', null, false],
                    ['csv_terminated', null, ','],
                    ['csv_enclosed', null, '"'],
                    ['csv_escaped', null, '"'],
                    ['csv_new_line', null, 'auto'],
                    ['csv_columns', null, ''],
                    ['csv_col_names', null, false],
                    ['ldi_replace', null, false],
                    ['ldi_ignore', null, false],
                    ['ldi_terminated', null, ';'],
                    ['ldi_enclosed', null, '"'],
                    ['ldi_escaped', null, '\\'],
                    ['ldi_new_line', null, 'auto'],
                    ['ldi_columns', null, ''],
                    ['ldi_local_option', null, 'auto'],
                    ['ods_col_names', null, false],
                    ['ods_empty_rows', null, true],
                    ['ods_recognize_percentages', null, true],
                    ['ods_recognize_currency', null, true],
                ],
            ],
            'valid values' => [
                [
                    ['format', 'csv', 'csv'],
                    ['charset', 'test', 'test'],
                    ['allow_interrupt', true, true],
                    ['skip_queries', 0, 0],
                    ['sql_compatibility', 'NONE', 'NONE'],
                    ['sql_no_auto_value_on_zero', true, true],
                    ['sql_read_as_multibytes', false, false],
                    ['csv_replace', false, false],
                    ['csv_ignore', false, false],
                    ['csv_terminated', 'test', 'test'],
                    ['csv_enclosed', 'test', 'test'],
                    ['csv_escaped', 'test', 'test'],
                    ['csv_new_line', 'test', 'test'],
                    ['csv_columns', 'test', 'test'],
                    ['csv_col_names', false, false],
                    ['ldi_replace', false, false],
                    ['ldi_ignore', false, false],
                    ['ldi_terminated', 'test', 'test'],
                    ['ldi_enclosed', 'test', 'test'],
                    ['ldi_escaped', 'test', 'test'],
                    ['ldi_new_line', 'test', 'test'],
                    ['ldi_columns', 'test', 'test'],
                    ['ldi_local_option', 'auto', 'auto'],
                    ['ods_col_names', false, false],
                    ['ods_empty_rows', true, true],
                    ['ods_recognize_percentages', true, true],
                    ['ods_recognize_currency', true, true],
                ],
            ],
            'valid values 2' => [
                [
                    ['format', 'docsql', 'docsql'],
                    ['allow_interrupt', false, false],
                    ['skip_queries', 1, 1],
                    ['sql_compatibility', 'ANSI', 'ANSI'],
                    ['sql_no_auto_value_on_zero', false, false],
                    ['sql_read_as_multibytes', true, true],
                    ['csv_replace', true, true],
                    ['csv_ignore', true, true],
                    ['csv_col_names', true, true],
                    ['ldi_replace', true, true],
                    ['ldi_ignore', true, true],
                    ['ldi_local_option', true, true],
                    ['ods_col_names', true, true],
                    ['ods_empty_rows', false, false],
                    ['ods_recognize_percentages', false, false],
                    ['ods_recognize_currency', false, false],
                ],
            ],
            'valid values 3' => [
                [
                    ['format', 'ldi', 'ldi'],
                    ['sql_compatibility', 'DB2', 'DB2'],
                    ['ldi_local_option', false, false],
                ],
            ],
            'valid values 4' => [
                [
                    ['format', 'sql', 'sql'],
                    ['sql_compatibility', 'MAXDB', 'MAXDB'],
                ],
            ],
            'valid values 5' => [[['sql_compatibility', 'MAXDB', 'MAXDB']]],
            'valid values 6' => [[['sql_compatibility', 'MYSQL323', 'MYSQL323']]],
            'valid values 7' => [[['sql_compatibility', 'MYSQL40', 'MYSQL40']]],
            'valid values 8' => [[['sql_compatibility', 'MSSQL', 'MSSQL']]],
            'valid values 9' => [[['sql_compatibility', 'ORACLE', 'ORACLE']]],
            'valid values 10' => [[['sql_compatibility', 'TRADITIONAL', 'TRADITIONAL']]],
            'valid values with type coercion' => [
                [
                    ['charset', 1234, '1234'],
                    ['allow_interrupt', 0, false],
                    ['skip_queries', '1', 1],
                    ['sql_no_auto_value_on_zero', 0, false],
                    ['sql_read_as_multibytes', 1, true],
                    ['csv_replace', 1, true],
                    ['csv_ignore', 1, true],
                    ['csv_terminated', 1234, '1234'],
                    ['csv_enclosed', 1234, '1234'],
                    ['csv_escaped', 1234, '1234'],
                    ['csv_new_line', 1234, '1234'],
                    ['csv_columns', 1234, '1234'],
                    ['csv_col_names', 1, true],
                    ['ldi_replace', 1, true],
                    ['ldi_ignore', 1, true],
                    ['ldi_terminated', 1234, '1234'],
                    ['ldi_enclosed', 1234, '1234'],
                    ['ldi_escaped', 1234, '1234'],
                    ['ldi_new_line', 1234, '1234'],
                    ['ldi_columns', 1234, '1234'],
                    ['ldi_local_option', '1', true],
                    ['ods_col_names', 1, true],
                    ['ods_empty_rows', 0, false],
                    ['ods_recognize_percentages', 0, false],
                    ['ods_recognize_currency', 0, false],
                ],
            ],
            'invalid values' => [
                [
                    ['format', 'invalid', 'sql'],
                    ['skip_queries', -1, 0],
                    ['sql_compatibility', 'invalid', 'NONE'],
                ],
            ],
        ];
    }
}
