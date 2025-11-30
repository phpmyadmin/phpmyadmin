<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Query;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\DbalInterface;
use PhpMyAdmin\Query\Compatibility;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpMyAdmin\Query\Compatibility
 */
class CompatibilityTest extends TestCase
{
    /**
     * @dataProvider providerForTestHasAccountLocking
     */
    public function testHasAccountLocking(bool $expected, bool $isMariaDb, int $version): void
    {
        self::assertSame($expected, Compatibility::hasAccountLocking($isMariaDb, $version));
    }

    /**
     * @return array[]
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

    /**
     * @dataProvider providerForTestIsUUIDSupported
     */
    public function testIsUUIDSupported(bool $expected, bool $isMariaDb, int $version): void
    {
        $dbiStub = $this->createStub(DatabaseInterface::class);

        $dbiStub->method('isMariaDB')->willReturn($isMariaDb);
        $dbiStub->method('getVersion')->willReturn($version);

        self::assertSame($expected, Compatibility::isUUIDSupported($dbiStub));
    }

    /**
     * @return array[]
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

    /**
     * @dataProvider providerForTestIsJsonSupported
     */
    public function testIsJsonSupported(bool $expected, bool $isMariaDb, int $version): void
    {
        $dbiStub = $this->createStub(DatabaseInterface::class);

        $dbiStub->method('isMariaDB')->willReturn($isMariaDb);
        $dbiStub->method('getVersion')->willReturn($version);

        self::assertSame($expected, Compatibility::isJsonSupported($dbiStub));
    }

    /**
     * @return array[]
     * @psalm-return array<string, array{bool, bool, int}>
     */
    public static function providerForTestIsJsonSupported(): array
    {
        return [
            'MySQL 5.7.7' => [false, false, 50707],
            'MySQL 5.7.8' => [false, true, 50708],
            'MariaDB 10.2.6' => [false, true, 100206],
            'MariaDB 10.2.7' => [true, true, 100207],
        ];
    }

    /** @dataProvider showBinLogStatusProvider */
    public function testGetShowBinLogStatusStmt(string $serverName, int $version, string $expected): void
    {
        $dbal = self::createStub(DbalInterface::class);
        $dbal->method('isMySql')->willReturn($serverName === 'MySQL');
        $dbal->method('isMariaDB')->willReturn($serverName === 'MariaDB');
        $dbal->method('getVersion')->willReturn($version);
        self::assertSame($expected, Compatibility::getShowBinLogStatusStmt($dbal));
    }

    /** @return iterable<int, array{string, int, string}> */
    public static function showBinLogStatusProvider(): iterable
    {
        yield ['MySQL', 80200, 'SHOW BINARY LOG STATUS'];
        yield ['MariaDB', 100502, 'SHOW BINLOG STATUS'];
        yield ['MySQL', 80199, 'SHOW MASTER STATUS'];
        yield ['MariaDB', 100501, 'SHOW MASTER STATUS'];
        yield ['MySQL', 100502, 'SHOW BINARY LOG STATUS'];
    }
}
