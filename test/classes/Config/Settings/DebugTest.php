<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Settings;

use PhpMyAdmin\Config\Settings\Debug;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_merge;

/**
 * @covers \PhpMyAdmin\Config\Settings\Debug
 */
class DebugTest extends TestCase
{
    /** @var array<string, bool> */
    private $defaultValues = ['sql' => false, 'sqllog' => false, 'demo' => false, 'simple2fa' => false];

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
        $settings = new Debug($actualValues);

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
                    ['sql', null, false],
                    ['sqllog', null, false],
                    ['demo', null, false],
                    ['simple2fa', null, false],
                ],
            ],
            'valid values' => [
                [
                    ['sql', false, false],
                    ['sqllog', false, false],
                    ['demo', false, false],
                    ['simple2fa', false, false],
                ],
            ],
            'valid values 2' => [
                [
                    ['sql', true, true],
                    ['sqllog', true, true],
                    ['demo', true, true],
                    ['simple2fa', true, true],
                ],
            ],
            'valid values with type coercion' => [
                [
                    ['sql', 1, true],
                    ['sqllog', 1, true],
                    ['demo', 1, true],
                    ['simple2fa', 1, true],
                ],
            ],
        ];
    }
}
