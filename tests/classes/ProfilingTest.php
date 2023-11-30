<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\DatabaseInterface;
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
        $GLOBALS['server'] = 1;

        SessionCache::set('profiling_supported', true);
        $condition = Profiling::isSupported($dbi);
        $this->assertTrue($condition);

        SessionCache::set('profiling_supported', false);
        $condition = Profiling::isSupported($dbi);
        $this->assertFalse($condition);
    }
}
