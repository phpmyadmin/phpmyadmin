<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Query;

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
        $this->assertSame($expected, Compatibility::hasAccountLocking($isMariaDb, $version));
    }

    /**
     * @return array[]
     * @psalm-return array<string, array{bool, bool, int}>
     */
    public function providerForTestHasAccountLocking(): array
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
}
