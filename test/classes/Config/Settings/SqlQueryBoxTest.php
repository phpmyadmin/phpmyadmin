<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Settings;

use PhpMyAdmin\Config\Settings\SqlQueryBox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SqlQueryBox::class)]
class SqlQueryBoxTest extends TestCase
{
    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testEdit(mixed $actual, bool $expected): void
    {
        $sqlQueryBox = new SqlQueryBox(['Edit' => $actual]);
        $sqlQueryBoxArray = $sqlQueryBox->asArray();
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->assertSame($expected, $sqlQueryBox->Edit);
        $this->assertArrayHasKey('Edit', $sqlQueryBoxArray);
        $this->assertSame($expected, $sqlQueryBoxArray['Edit']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testExplain(mixed $actual, bool $expected): void
    {
        $sqlQueryBox = new SqlQueryBox(['Explain' => $actual]);
        $sqlQueryBoxArray = $sqlQueryBox->asArray();
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->assertSame($expected, $sqlQueryBox->Explain);
        $this->assertArrayHasKey('Explain', $sqlQueryBoxArray);
        $this->assertSame($expected, $sqlQueryBoxArray['Explain']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testShowAsPHP(mixed $actual, bool $expected): void
    {
        $sqlQueryBox = new SqlQueryBox(['ShowAsPHP' => $actual]);
        $sqlQueryBoxArray = $sqlQueryBox->asArray();
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->assertSame($expected, $sqlQueryBox->ShowAsPHP);
        $this->assertArrayHasKey('ShowAsPHP', $sqlQueryBoxArray);
        $this->assertSame($expected, $sqlQueryBoxArray['ShowAsPHP']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
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
