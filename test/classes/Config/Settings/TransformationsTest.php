<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Settings;

use PhpMyAdmin\Config\Settings\Transformations;
use PHPUnit\Framework\TestCase;

/** @covers \PhpMyAdmin\Config\Settings\Transformations */
class TransformationsTest extends TestCase
{
    /**
     * @param array<int, int|string> $expected
     *
     * @dataProvider valuesForSubstringProvider
     */
    public function testSubstring(mixed $actual, array $expected): void
    {
        $transformations = new Transformations(['Substring' => $actual]);
        $transformationsArray = $transformations->asArray();
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->assertSame($expected, $transformations->Substring);
        $this->assertArrayHasKey('Substring', $transformationsArray);
        $this->assertSame($expected, $transformationsArray['Substring']);
    }

    /** @return iterable<string, array{mixed, array<int, int|string>}> */
    public static function valuesForSubstringProvider(): iterable
    {
        yield 'null value' => [null, [0, 'all', '…']];
        yield 'valid value' => [[], [0, 'all', '…']];
        yield 'valid value 2' => [[0, 'all', 'test'], [0, 'all', 'test']];
        yield 'valid value 3' => [[1, 1, 'test'], [1, 1, 'test']];
        yield 'valid value 4' => [[null, null, 'test'], [0, 'all', 'test']];
        yield 'valid value 5' => [[null, 0], [0, 0, '…']];
        yield 'valid value 6' => [[1], [1, 'all', '…']];
        yield 'valid value with type coercion' => [['1', '-1', 1234], [1, -1, '1234']];
        yield 'invalid value' => ['invalid', [0, 'all', '…']];
    }

    /**
     * @param array<int, string> $expected
     *
     * @dataProvider valuesForBool2TextProvider
     */
    public function testBool2Text(mixed $actual, array $expected): void
    {
        $transformations = new Transformations(['Bool2Text' => $actual]);
        $transformationsArray = $transformations->asArray();
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->assertSame($expected, $transformations->Bool2Text);
        $this->assertArrayHasKey('Bool2Text', $transformationsArray);
        $this->assertSame($expected, $transformationsArray['Bool2Text']);
    }

    /** @return iterable<string, array{mixed, array<int, string>}> */
    public static function valuesForBool2TextProvider(): iterable
    {
        yield 'null value' => [null, ['T', 'F']];
        yield 'valid value' => [[], ['T', 'F']];
        yield 'valid value 2' => [['true', 'false'], ['true', 'false']];
        yield 'valid value 3' => [[null, 'test'], ['T', 'test']];
        yield 'valid value 4' => [['test'], ['test', 'F']];
        yield 'valid value with type coercion' => [[1, 0], ['1', '0']];
        yield 'invalid value' => ['invalid', ['T', 'F']];
    }

    /**
     * @param array<int, int|string> $expected
     *
     * @dataProvider valuesForExternalProvider
     */
    public function testExternal(mixed $actual, array $expected): void
    {
        $transformations = new Transformations(['External' => $actual]);
        $transformationsArray = $transformations->asArray();
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->assertSame($expected, $transformations->External);
        $this->assertArrayHasKey('External', $transformationsArray);
        $this->assertSame($expected, $transformationsArray['External']);
    }

    /** @return iterable<string, array{mixed, array<int, int|string>}> */
    public static function valuesForExternalProvider(): iterable
    {
        yield 'null values' => [null, [0, '-f /dev/null -i -wrap -q', 1, 1]];
        yield 'valid values' => [[], [0, '-f /dev/null -i -wrap -q', 1, 1]];
        yield 'valid values 2' => [[0, 'test', 1, 1], [0, 'test', 1, 1]];
        yield 'valid values 3' => [[1, 'test', 0, 0], [1, 'test', 0, 0]];
        yield 'valid values 4' => [[null, null, null, 0], [0, '-f /dev/null -i -wrap -q', 1, 0]];
        yield 'valid values 5' => [[null, null, 0], [0, '-f /dev/null -i -wrap -q', 0, 1]];
        yield 'valid values 6' => [[null, 'test'], [0, 'test', 1, 1]];
        yield 'valid values 7' => [[1], [1, '-f /dev/null -i -wrap -q', 1, 1]];
        yield 'valid values with type coercion' => [['1', 1234, '0', '1'], [1, '1234', 0, 1]];
        yield 'invalid values' => ['invalid', [0, '-f /dev/null -i -wrap -q', 1, 1]];
    }

    /**
     * @param array<int, string> $expected
     *
     * @dataProvider valuesForPreApPendProvider
     */
    public function testPreApPend(mixed $actual, array $expected): void
    {
        $transformations = new Transformations(['PreApPend' => $actual]);
        $transformationsArray = $transformations->asArray();
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->assertSame($expected, $transformations->PreApPend);
        $this->assertArrayHasKey('PreApPend', $transformationsArray);
        $this->assertSame($expected, $transformationsArray['PreApPend']);
    }

    /** @return iterable<string, array{mixed, array<int, string>}> */
    public static function valuesForPreApPendProvider(): iterable
    {
        yield 'null values' => [null, ['', '']];
        yield 'valid values' => [[], ['', '']];
        yield 'valid values 2' => [['test1', 'test2'], ['test1', 'test2']];
        yield 'valid values 3' => [[null, 'test'], ['', 'test']];
        yield 'valid values 4' => [['test'], ['test', '']];
        yield 'valid values with type coercion' => [[1234, 1234], ['1234', '1234']];
        yield 'invalid values' => ['invalid', ['', '']];
    }

    /**
     * @param array<int, int> $expected
     *
     * @dataProvider valuesForHexProvider
     */
    public function testHex(mixed $actual, array $expected): void
    {
        $transformations = new Transformations(['Hex' => $actual]);
        $transformationsArray = $transformations->asArray();
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->assertSame($expected, $transformations->Hex);
        $this->assertArrayHasKey('Hex', $transformationsArray);
        $this->assertSame($expected, $transformationsArray['Hex']);
    }

    /** @return iterable<string, array{mixed, array<int, int>}> */
    public static function valuesForHexProvider(): iterable
    {
        yield 'null values' => [null, [2]];
        yield 'valid values' => [[], [2]];
        yield 'valid values 2' => [[0], [0]];
        yield 'valid values 3' => [[1], [1]];
        yield 'valid values with type coercion' => [['1'], [1]];
        yield 'invalid values' => ['invalid', [2]];
    }

    /**
     * @param array<int, int|string> $expected
     *
     * @dataProvider valuesForDateFormatProvider
     */
    public function testDateFormat(mixed $actual, array $expected): void
    {
        $transformations = new Transformations(['DateFormat' => $actual]);
        $transformationsArray = $transformations->asArray();
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->assertSame($expected, $transformations->DateFormat);
        $this->assertArrayHasKey('DateFormat', $transformationsArray);
        $this->assertSame($expected, $transformationsArray['DateFormat']);
    }

    /** @return iterable<string, array{mixed, array<int, int|string>}> */
    public static function valuesForDateFormatProvider(): iterable
    {
        yield 'null values' => [null, [0, '', 'local']];
        yield 'valid values' => [[], [0, '', 'local']];
        yield 'valid values 2' => [[0, 'test', 'local'], [0, 'test', 'local']];
        yield 'valid values 3' => [[1, 'test', 'utc'], [1, 'test', 'utc']];
        yield 'valid values 4' => [[null, null, 'utc'], [0, '', 'utc']];
        yield 'valid values 5' => [[null, 'test'], [0, 'test', 'local']];
        yield 'valid values 6' => [[1], [1, '', 'local']];
        yield 'valid values with type coercion' => [['1', 1234, 'local'], [1, '1234', 'local']];
        yield 'invalid values' => ['invalid', [0, '', 'local']];
    }

    /**
     * @param array<int|string, int|string|array<string>|null> $expected
     *
     * @dataProvider valuesForInlineProvider
     */
    public function testInline(mixed $actual, array $expected): void
    {
        $transformations = new Transformations(['Inline' => $actual]);
        $transformationsArray = $transformations->asArray();
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->assertSame($expected, $transformations->Inline);
        $this->assertArrayHasKey('Inline', $transformationsArray);
        $this->assertSame($expected, $transformationsArray['Inline']);
    }

    /** @return iterable<string, array{mixed, array<int|string, int|string|array<string, string>|null>}> */
    public static function valuesForInlineProvider(): iterable
    {
        yield 'null values' => [null, [100, 100, 'wrapper_link' => null, 'wrapper_params' => []]];
        yield 'valid values' => [[], [100, 100, 'wrapper_link' => null, 'wrapper_params' => []]];
        yield 'valid values 2' => [
            [0, 0, 'wrapper_link' => 'test', 'wrapper_params' => ['key' => 'value']],
            [0, 0, 'wrapper_link' => 'test', 'wrapper_params' => ['key' => 'value']],
        ];

        yield 'valid values 3' => [[1, 1], [1, 1, 'wrapper_link' => null, 'wrapper_params' => []]];
        yield 'valid values 4' => [[null, 1], [100, 1, 'wrapper_link' => null, 'wrapper_params' => []]];
        yield 'valid values 5' => [[1], [1, 100, 'wrapper_link' => null, 'wrapper_params' => []]];
        yield 'valid values with type coercion' => [
            ['1', '2', 'wrapper_link' => 1234, 'wrapper_params' => ['key' => 1234]],
            [1, 2, 'wrapper_link' => '1234', 'wrapper_params' => ['key' => '1234']],
        ];

        yield 'invalid values' => ['invalid', [100, 100, 'wrapper_link' => null, 'wrapper_params' => []]];
    }

    /**
     * @param array<int, int|string|null> $expected
     *
     * @dataProvider valuesForTextImageLinkProvider
     */
    public function testTextImageLink(mixed $actual, array $expected): void
    {
        $transformations = new Transformations(['TextImageLink' => $actual]);
        $transformationsArray = $transformations->asArray();
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->assertSame($expected, $transformations->TextImageLink);
        $this->assertArrayHasKey('TextImageLink', $transformationsArray);
        $this->assertSame($expected, $transformationsArray['TextImageLink']);
    }

    /** @return iterable<string, array{mixed, array<int, int|string|null>}> */
    public static function valuesForTextImageLinkProvider(): iterable
    {
        yield 'null values' => [null, [null, 100, 50]];
        yield 'valid values' => [[], [null, 100, 50]];
        yield 'valid values 2' => [['test', 0, 0], ['test', 0, 0]];
        yield 'valid values 3' => [['test', 1, 1], ['test', 1, 1]];
        yield 'valid values 4' => [[null, null, 1], [null, 100, 1]];
        yield 'valid values 5' => [[null, 1], [null, 1, 50]];
        yield 'valid values 6' => [['test'], ['test', 100, 50]];
        yield 'valid values with type coercion' => [[1234, '1', '2'], ['1234', 1, 2]];
        yield 'invalid values' => ['invalid', [null, 100, 50]];
    }

    /**
     * @param array<int, string|bool|null> $expected
     *
     * @dataProvider valuesForTextLinkProvider
     */
    public function testTextLink(mixed $actual, array $expected): void
    {
        $transformations = new Transformations(['TextLink' => $actual]);
        $transformationsArray = $transformations->asArray();
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->assertSame($expected, $transformations->TextLink);
        $this->assertArrayHasKey('TextLink', $transformationsArray);
        $this->assertSame($expected, $transformationsArray['TextLink']);
    }

    /** @return iterable<string, array{mixed, array<int, string|bool|null>}> */
    public static function valuesForTextLinkProvider(): iterable
    {
        yield 'null values' => [null, [null, null, null]];
        yield 'valid values' => [[], [null, null, null]];
        yield 'valid values 2' => [['test1', 'test2', true], ['test1', 'test2', true]];
        yield 'valid values 3' => [['test1', 'test2', false], ['test1', 'test2', false]];
        yield 'valid values 4' => [[null, null, true], [null, null, true]];
        yield 'valid values 5' => [[null, 'test'], [null, 'test', null]];
        yield 'valid values 6' => [['test'], ['test', null, null]];
        yield 'valid values with type coercion' => [[1234, 1234, 1], ['1234', '1234', true]];
        yield 'invalid values' => ['invalid', [null, null, null]];
    }
}
