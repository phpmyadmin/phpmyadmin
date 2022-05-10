<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Settings;

use PhpMyAdmin\Config\Settings\Schema;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_merge;

/**
 * @covers \PhpMyAdmin\Config\Settings\Schema
 */
class SchemaTest extends TestCase
{
    /** @var array<string, bool|string> */
    private $defaultValues = [
        'format' => 'pdf',
        'pdf_show_color' => true,
        'pdf_show_keys' => false,
        'pdf_all_tables_same_width' => false,
        'pdf_orientation' => 'L',
        'pdf_paper' => 'A4',
        'pdf_show_grid' => false,
        'pdf_with_doc' => true,
        'pdf_table_order' => '',
        'dia_show_color' => true,
        'dia_show_keys' => false,
        'dia_orientation' => 'L',
        'dia_paper' => 'A4',
        'eps_show_color' => true,
        'eps_show_keys' => false,
        'eps_all_tables_same_width' => false,
        'eps_orientation' => 'L',
        'svg_show_color' => true,
        'svg_show_keys' => false,
        'svg_all_tables_same_width' => false,
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
        $settings = new Schema($actualValues);

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
                    ['format', null, 'pdf'],
                    ['pdf_show_color', null, true],
                    ['pdf_show_keys', null, false],
                    ['pdf_all_tables_same_width', null, false],
                    ['pdf_orientation', null, 'L'],
                    ['pdf_paper', null, 'A4'],
                    ['pdf_show_grid', null, false],
                    ['pdf_with_doc', null, true],
                    ['pdf_table_order', null, ''],
                    ['dia_show_color', null, true],
                    ['dia_show_keys', null, false],
                    ['dia_orientation', null, 'L'],
                    ['dia_paper', null, 'A4'],
                    ['eps_show_color', null, true],
                    ['eps_show_keys', null, false],
                    ['eps_all_tables_same_width', null, false],
                    ['eps_orientation', null, 'L'],
                    ['svg_show_color', null, true],
                    ['svg_show_keys', null, false],
                    ['svg_all_tables_same_width', null, false],
                ],
            ],
            'valid values' => [
                [
                    ['format', 'pdf', 'pdf'],
                    ['pdf_show_color', true, true],
                    ['pdf_show_keys', false, false],
                    ['pdf_all_tables_same_width', false, false],
                    ['pdf_orientation', 'L', 'L'],
                    ['pdf_paper', 'test', 'test'],
                    ['pdf_show_grid', false, false],
                    ['pdf_with_doc', true, true],
                    ['pdf_table_order', '', ''],
                    ['dia_show_color', true, true],
                    ['dia_show_keys', false, false],
                    ['dia_orientation', 'L', 'L'],
                    ['dia_paper', 'test', 'test'],
                    ['eps_show_color', true, true],
                    ['eps_show_keys', false, false],
                    ['eps_all_tables_same_width', false, false],
                    ['eps_orientation', 'L', 'L'],
                    ['svg_show_color', true, true],
                    ['svg_show_keys', false, false],
                    ['svg_all_tables_same_width', false, false],
                ],
            ],
            'valid values 2' => [
                [
                    ['format', 'eps', 'eps'],
                    ['pdf_show_color', false, false],
                    ['pdf_show_keys', true, true],
                    ['pdf_all_tables_same_width', true, true],
                    ['pdf_orientation', 'P', 'P'],
                    ['pdf_show_grid', true, true],
                    ['pdf_with_doc', false, false],
                    ['pdf_table_order', 'name_asc', 'name_asc'],
                    ['dia_show_color', false, false],
                    ['dia_show_keys', true, true],
                    ['dia_orientation', 'P', 'P'],
                    ['eps_show_color', false, false],
                    ['eps_show_keys', true, true],
                    ['eps_all_tables_same_width', true, true],
                    ['eps_orientation', 'P', 'P'],
                    ['svg_show_color', false, false],
                    ['svg_show_keys', true, true],
                    ['svg_all_tables_same_width', true, true],
                ],
            ],
            'valid values 3' => [
                [
                    ['format', 'dia', 'dia'],
                    ['pdf_table_order', 'name_desc', 'name_desc'],
                ],
            ],
            'valid values 4' => [[['format', 'svg', 'svg']]],
            'valid values with type coercion' => [
                [
                    ['pdf_show_color', 0, false],
                    ['pdf_show_keys', 1, true],
                    ['pdf_all_tables_same_width', 1, true],
                    ['pdf_paper', 1234, '1234'],
                    ['pdf_show_grid', 1, true],
                    ['pdf_with_doc', 0, false],
                    ['dia_show_color', 0, false],
                    ['dia_show_keys', 1, true],
                    ['dia_paper', 1234, '1234'],
                    ['eps_show_color', 0, false],
                    ['eps_show_keys', 1, true],
                    ['eps_all_tables_same_width', 1, true],
                    ['svg_show_color', 0, false],
                    ['svg_show_keys', 1, true],
                    ['svg_all_tables_same_width', 1, true],
                ],
            ],
            'invalid values' => [
                [
                    ['format', 'invalid', 'pdf'],
                    ['pdf_orientation', 'invalid', 'L'],
                    ['pdf_table_order', 'invalid', ''],
                    ['dia_orientation', 'invalid', 'L'],
                    ['eps_orientation', 'invalid', 'L'],
                ],
            ],
        ];
    }
}
