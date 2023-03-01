<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Settings;

use PhpMyAdmin\Config\Settings\SqlQueryBox;
use PHPUnit\Framework\TestCase;

/** @covers \PhpMyAdmin\Config\Settings\SqlQueryBox */
class SqlQueryBoxTest extends TestCase
{
    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testEdit(mixed $actual, bool $expected): void
    {
        $sqlQueryBox = new SqlQueryBox(['Edit' => $actual]);
        $sqlQueryBoxArray = $sqlQueryBox->asArray();
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->assertSame($expected, $sqlQueryBox->Edit);
        $this->assertArrayHasKey('Edit', $sqlQueryBoxArray);
        $this->assertSame($expected, $sqlQueryBoxArray['Edit']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testExplain(mixed $actual, bool $expected): void
    {
        $sqlQueryBox = new SqlQueryBox(['Explain' => $actual]);
        $sqlQueryBoxArray = $sqlQueryBox->asArray();
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->assertSame($expected, $sqlQueryBox->Explain);
        $this->assertArrayHasKey('Explain', $sqlQueryBoxArray);
        $this->assertSame($expected, $sqlQueryBoxArray['Explain']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testShowAsPHP(mixed $actual, bool $expected): void
    {
        $sqlQueryBox = new SqlQueryBox(['ShowAsPHP' => $actual]);
        $sqlQueryBoxArray = $sqlQueryBox->asArray();
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->assertSame($expected, $sqlQueryBox->ShowAsPHP);
        $this->assertArrayHasKey('ShowAsPHP', $sqlQueryBoxArray);
        $this->assertSame($expected, $sqlQueryBoxArray['ShowAsPHP']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testRefresh(mixed $actual, bool $expected): void
    {
        $sqlQueryBox = new SqlQueryBox(['Refresh' => $actual]);
        $sqlQueryBoxArray = $sqlQueryBox->asArray();
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->assertSame($expected, $sqlQueryBox->Refresh);
        $this->assertArrayHasKey('Refresh', $sqlQueryBoxArray);
        $this->assertSame($expected, $sqlQueryBoxArray['Refresh']);
    }

    /** @return iterable<string, array{mixed, bool}> */
    public static function booleanWithDefaultTrueProvider(): iterable
    {
        yield 'null value' => [null, true];
        yield 'valid value' => [true, true];
        yield 'valid value 2' => [false, false];
        yield 'valid value with type coercion' => [0, false];
    }
}
