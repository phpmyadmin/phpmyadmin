<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Profiling;
use PhpMyAdmin\Utils\SessionCache;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @covers \PhpMyAdmin\Profiling
 */
#[CoversClass(Profiling::class)]
class ProfilingTest extends AbstractTestCase
{
    public function testIsSupported(): void
    {
        global $dbi, $server;

        $server = 1;

        SessionCache::set('profiling_supported', true);
        $condition = Profiling::isSupported($dbi);
        self::assertTrue($condition);

        SessionCache::set('profiling_supported', false);
        $condition = Profiling::isSupported($dbi);
        self::assertFalse($condition);
    }
}
