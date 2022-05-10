<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Settings;

use PhpMyAdmin\Config\Settings\Console;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_merge;

/**
 * @covers \PhpMyAdmin\Config\Settings\Console
 */
class ConsoleTest extends TestCase
{
    /** @var array<string, bool|int|string> */
    private $defaultValues = [
        'StartHistory' => false,
        'AlwaysExpand' => false,
        'CurrentQuery' => true,
        'EnterExecutes' => false,
        'DarkTheme' => false,
        'Mode' => 'info',
        'Height' => 92,
        'GroupQueries' => false,
        'OrderBy' => 'exec',
        'Order' => 'asc',
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
        $settings = new Console($actualValues);

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
                    ['StartHistory', null, false],
                    ['AlwaysExpand', null, false],
                    ['CurrentQuery', null, true],
                    ['EnterExecutes', null, false],
                    ['DarkTheme', null, false],
                    ['Mode', null, 'info'],
                    ['Height', null, 92],
                    ['GroupQueries', null, false],
                    ['OrderBy', null, 'exec'],
                    ['Order', null, 'asc'],
                ],
            ],
            'valid values' => [
                [
                    ['StartHistory', false, false],
                    ['AlwaysExpand', false, false],
                    ['CurrentQuery', true, true],
                    ['EnterExecutes', false, false],
                    ['DarkTheme', false, false],
                    ['Mode', 'info', 'info'],
                    ['Height', 1, 1],
                    ['GroupQueries', false, false],
                    ['OrderBy', 'exec', 'exec'],
                    ['Order', 'asc', 'asc'],
                ],
            ],
            'valid values 2' => [
                [
                    ['StartHistory', true, true],
                    ['AlwaysExpand', true, true],
                    ['CurrentQuery', false, false],
                    ['EnterExecutes', true, true],
                    ['DarkTheme', true, true],
                    ['Mode', 'show', 'show'],
                    ['GroupQueries', true, true],
                    ['OrderBy', 'time', 'time'],
                    ['Order', 'desc', 'desc'],
                ],
            ],
            'valid values 3' => [
                [
                    ['Mode', 'collapse', 'collapse'],
                    ['OrderBy', 'count', 'count'],
                ],
            ],
            'valid values with type coercion' => [
                [
                    ['StartHistory', 1, true],
                    ['AlwaysExpand', 1, true],
                    ['CurrentQuery', 0, false],
                    ['EnterExecutes', 1, true],
                    ['DarkTheme', 1, true],
                    ['Height', '2', 2],
                    ['GroupQueries', 1, true],
                ],
            ],
            'invalid values' => [
                [
                    ['Mode', 'invalid', 'info'],
                    ['Height', 0, 92],
                    ['OrderBy', 'invalid', 'exec'],
                    ['Order', 'invalid', 'asc'],
                ],
            ],
        ];
    }
}
