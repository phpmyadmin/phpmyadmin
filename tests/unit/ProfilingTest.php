<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Profiling;
use PhpMyAdmin\Utils\SessionCache;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Profiling::class)]
class ProfilingTest extends AbstractTestCase
{
    public function testIsSupported(): void
    {
        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;

        SessionCache::set('profiling_supported', true);
        $condition = Profiling::isSupported($dbi);
        self::assertTrue($condition);

        SessionCache::set('profiling_supported', false);
        $condition = Profiling::isSupported($dbi);
        self::assertFalse($condition);
    }
}
