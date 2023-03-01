<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Settings;

use PhpMyAdmin\Config\Settings\Debug;
use PHPUnit\Framework\TestCase;

/** @covers \PhpMyAdmin\Config\Settings\Debug */
class DebugTest extends TestCase
{
    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSql(mixed $actual, bool $expected): void
    {
        $debug = new Debug(['sql' => $actual]);
        $debugArray = $debug->asArray();
        $this->assertSame($expected, $debug->sql);
        $this->assertArrayHasKey('sql', $debugArray);
        $this->assertSame($expected, $debugArray['sql']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSqllog(mixed $actual, bool $expected): void
    {
        $debug = new Debug(['sqllog' => $actual]);
        $debugArray = $debug->asArray();
        $this->assertSame($expected, $debug->sqllog);
        $this->assertArrayHasKey('sqllog', $debugArray);
        $this->assertSame($expected, $debugArray['sqllog']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testDemo(mixed $actual, bool $expected): void
    {
        $debug = new Debug(['demo' => $actual]);
        $debugArray = $debug->asArray();
        $this->assertSame($expected, $debug->demo);
        $this->assertArrayHasKey('demo', $debugArray);
        $this->assertSame($expected, $debugArray['demo']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSimple2fa(mixed $actual, bool $expected): void
    {
        $debug = new Debug(['simple2fa' => $actual]);
        $debugArray = $debug->asArray();
        $this->assertSame($expected, $debug->simple2fa);
        $this->assertArrayHasKey('simple2fa', $debugArray);
        $this->assertSame($expected, $debugArray['simple2fa']);
    }

    /** @return iterable<string, array{mixed, bool}> */
    public static function booleanWithDefaultFalseProvider(): iterable
    {
        yield 'null value' => [null, false];
        yield 'valid value' => [false, false];
        yield 'valid value 2' => [true, true];
        yield 'valid value with type coercion' => [1, true];
    }
}
