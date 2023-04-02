<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Query;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Query\Compatibility;
use PHPUnit\Framework\TestCase;

/** @covers \PhpMyAdmin\Query\Compatibility */
class CompatibilityTest extends TestCase
{
    /** @dataProvider providerForTestHasAccountLocking */
    public function testHasAccountLocking(bool $expected, bool $isMariaDb, int $version): void
    {
        $this->assertSame($expected, Compatibility::hasAccountLocking($isMariaDb, $version));
    }

    /**
     * @return mixed[][]
     * @psalm-return array<string, array{bool, bool, int}>
     */
    public static function providerForTestHasAccountLocking(): array
    {
        return [
            'MySQL 5.7.5' => [false, false, 50705],
            'MySQL 5.7.6' => [true, false, 50706],
            'MySQL 5.7.7' => [true, false, 50707],
            'MariaDB 10.4.1' => [false, true, 100401],
            'MariaDB 10.4.2' => [true, true, 100402],
            'MariaDB 10.4.3' => [true, true, 100403],
        ];
    }

    /** @dataProvider providerForTestIsUUIDSupported */
    public function testIsUUIDSupported(bool $expected, bool $isMariaDb, int $version): void
    {
        $dbiStub = $this->createStub(DatabaseInterface::class);

        $dbiStub->method('isMariaDB')->willReturn($isMariaDb);
        $dbiStub->method('getVersion')->willReturn($version);

        $this->assertSame($expected, Compatibility::isUUIDSupported($dbiStub));
    }

    /**
     * @return mixed[][]
     * @psalm-return array<string, array{bool, bool, int}>
     */
    public static function providerForTestIsUUIDSupported(): array
    {
        return [
            'MySQL 5.7.5' => [false, false, 50705],
            'MySQL 8.0.30' => [false, false, 80030],
            'MariaDB 10.6.0' => [false, true, 100600],
            'MariaDB 10.7.0' => [true, true, 100700],
        ];
    }
}
