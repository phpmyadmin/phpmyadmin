<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Settings;

use PhpMyAdmin\Config\Settings\SqlQueryBox;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_merge;

/**
 * @covers \PhpMyAdmin\Config\Settings\SqlQueryBox
 */
class SqlQueryBoxTest extends TestCase
{
    /** @var array<string, bool> */
    private $defaultValues = ['Edit' => true, 'Explain' => true, 'ShowAsPHP' => true, 'Refresh' => true];

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
        $settings = new SqlQueryBox($actualValues);

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
                    ['Edit', null, true],
                    ['Explain', null, true],
                    ['ShowAsPHP', null, true],
                    ['Refresh', null, true],
                ],
            ],
            'valid values' => [
                [
                    ['Edit', true, true],
                    ['Explain', true, true],
                    ['ShowAsPHP', true, true],
                    ['Refresh', true, true],
                ],
            ],
            'valid values 2' => [
                [
                    ['Edit', false, false],
                    ['Explain', false, false],
                    ['ShowAsPHP', false, false],
                    ['Refresh', false, false],
                ],
            ],
            'valid values with type coercion' => [
                [
                    ['Edit', 0, false],
                    ['Explain', 0, false],
                    ['ShowAsPHP', 0, false],
                    ['Refresh', 0, false],
                ],
            ],
        ];
    }
}
