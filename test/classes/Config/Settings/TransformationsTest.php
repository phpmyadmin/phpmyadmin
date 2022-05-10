<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Settings;

use PhpMyAdmin\Config\Settings\Transformations;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_merge;

// phpcs:disable Generic.Files.LineLength.TooLong

/**
 * @covers \PhpMyAdmin\Config\Settings\Transformations
 */
class TransformationsTest extends TestCase
{
    /** @var array<string, array<int|string, array|int|string|null>> */
    private $defaultValues = [
        'Substring' => [0, 'all', '…'],
        'Bool2Text' => ['T', 'F'],
        'External' => [0, '-f /dev/null -i -wrap -q', 1, 1],
        'PreApPend' => ['', ''],
        'Hex' => [2],
        'DateFormat' => [0, '', 'local'],
        'Inline' => [100, 100, 'wrapper_link' => null, 'wrapper_params' => []],
        'TextImageLink' => [null, 100, 50],
        'TextLink' => [null, null, null],
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
        $settings = new Transformations($actualValues);

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
                    ['Substring', null, [0, 'all', '…']],
                    ['Bool2Text', null, ['T', 'F']],
                    ['External', null, [0, '-f /dev/null -i -wrap -q', 1, 1]],
                    ['PreApPend', null, ['', '']],
                    ['Hex', null, [2]],
                    ['DateFormat', null, [0, '', 'local']],
                    ['Inline', null, [100, 100, 'wrapper_link' => null, 'wrapper_params' => []]],
                    ['TextImageLink', null, [null, 100, 50]],
                    ['TextLink', null, [null, null, null]],
                ],
            ],
            'valid values' => [
                [
                    ['Substring', [], [0, 'all', '…']],
                    ['Bool2Text', [], ['T', 'F']],
                    ['External', [], [0, '-f /dev/null -i -wrap -q', 1, 1]],
                    ['PreApPend', [], ['', '']],
                    ['Hex', [], [2]],
                    ['DateFormat', [], [0, '', 'local']],
                    ['Inline', [], [100, 100, 'wrapper_link' => null, 'wrapper_params' => []]],
                    ['TextImageLink', [], [null, 100, 50]],
                    ['TextLink', [], [null, null, null]],
                ],
            ],
            'valid values 2' => [
                [
                    ['Substring', [0, 'all', 'test'], [0, 'all', 'test']],
                    ['Bool2Text', ['true', 'false'], ['true', 'false']],
                    ['External', [0, 'test', 1, 1], [0, 'test', 1, 1]],
                    ['PreApPend', ['test1', 'test2'], ['test1', 'test2']],
                    ['Hex', [0], [0]],
                    ['DateFormat', [0, 'test', 'local'], [0, 'test', 'local']],
                    ['Inline', [0, 0, 'wrapper_link' => 'test', 'wrapper_params' => ['key' => 'value']], [0, 0, 'wrapper_link' => 'test', 'wrapper_params' => ['key' => 'value']]],
                    ['TextImageLink', ['test', 0, 0], ['test', 0, 0]],
                    ['TextLink', ['test1', 'test2', true], ['test1', 'test2', true]],
                ],
            ],
            'valid values 3' => [
                [
                    ['Substring', [1, 1, 'test'], [1, 1, 'test']],
                    ['External', [1, 'test', 0, 0], [1, 'test', 0, 0]],
                    ['Hex', [1], [1]],
                    ['DateFormat', [1, 'test', 'utc'], [1, 'test', 'utc']],
                    ['Inline', [1, 1], [1, 1, 'wrapper_link' => null, 'wrapper_params' => []]],
                    ['TextImageLink', ['test', 1, 1], ['test', 1, 1]],
                    ['TextLink', ['test1', 'test2', false], ['test1', 'test2', false]],
                ],
            ],
            'valid values 4' => [
                [
                    ['Substring', [null, null, 'test'], [0, 'all', 'test']],
                    ['Bool2Text', [null, 'test'], ['T', 'test']],
                    ['External', [null, null, null, 0], [0, '-f /dev/null -i -wrap -q', 1, 0]],
                    ['PreApPend', [null, 'test'], ['', 'test']],
                    ['DateFormat', [null, null, 'utc'], [0, '', 'utc']],
                    ['Inline', [null, 1], [100, 1, 'wrapper_link' => null, 'wrapper_params' => []]],
                    ['TextImageLink', [null, null, 1], [null, 100, 1]],
                    ['TextLink', [null, null, true], [null, null, true]],
                ],
            ],
            'valid values 5' => [
                [
                    ['Substring', [null, 0], [0, 0, '…']],
                    ['Bool2Text', ['test'], ['test', 'F']],
                    ['External', [null, null, 0], [0, '-f /dev/null -i -wrap -q', 0, 1]],
                    ['PreApPend', ['test'], ['test', '']],
                    ['DateFormat', [null, 'test'], [0, 'test', 'local']],
                    ['Inline', [1], [1, 100, 'wrapper_link' => null, 'wrapper_params' => []]],
                    ['TextImageLink', [null, 1], [null, 1, 50]],
                    ['TextLink', [null, 'test'], [null, 'test', null]],
                ],
            ],
            'valid values 6' => [
                [
                    ['Substring', [1], [1, 'all', '…']],
                    ['External', [null, 'test'], [0, 'test', 1, 1]],
                    ['DateFormat', [1], [1, '', 'local']],
                    ['TextImageLink', ['test'], ['test', 100, 50]],
                    ['TextLink', ['test'], ['test', null, null]],
                ],
            ],
            'valid values 7' => [[['External', [1], [1, '-f /dev/null -i -wrap -q', 1, 1]]]],
            'valid values with type coercion' => [
                [
                    ['Substring', ['1', '-1', 1234], [1, -1, '1234']],
                    ['Bool2Text', [1, 0], ['1', '0']],
                    ['External', ['1', 1234, '0', '1'], [1, '1234', 0, 1]],
                    ['PreApPend', [1234, 1234], ['1234', '1234']],
                    ['Hex', ['1'], [1]],
                    ['DateFormat', ['1', 1234, 'local'], [1, '1234', 'local']],
                    ['Inline', ['1', '2', 'wrapper_link' => 1234, 'wrapper_params' => ['key' => 1234]], [1, 2, 'wrapper_link' => '1234', 'wrapper_params' => ['key' => '1234']]],
                    ['TextImageLink', [1234, '1', '2'], ['1234', 1, 2]],
                    ['TextLink', [1234, 1234, 1], ['1234', '1234', true]],
                ],
            ],
            'invalid values' => [
                [
                    ['Substring', 'invalid', [0, 'all', '…']],
                    ['Bool2Text', 'invalid', ['T', 'F']],
                    ['External', 'invalid', [0, '-f /dev/null -i -wrap -q', 1, 1]],
                    ['PreApPend', 'invalid', ['', '']],
                    ['Hex', 'invalid', [2]],
                    ['DateFormat', 'invalid', [0, '', 'local']],
                    ['Inline', 'invalid', [100, 100, 'wrapper_link' => null, 'wrapper_params' => []]],
                    ['TextImageLink', 'invalid', [null, 100, 50]],
                    ['TextLink', 'invalid', [null, null, null]],
                ],
            ],
        ];
    }
}
