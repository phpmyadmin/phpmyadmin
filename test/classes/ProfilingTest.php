<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Profiling;
use PhpMyAdmin\Utils\SessionCache;

/** @covers \PhpMyAdmin\Profiling */
class ProfilingTest extends AbstractTestCase
{
    public function testIsSupported(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $GLOBALS['server'] = 1;

        SessionCache::set('profiling_supported', true);
        $condition = Profiling::isSupported($GLOBALS['dbi']);
        $this->assertTrue($condition);

        SessionCache::set('profiling_supported', false);
        $condition = Profiling::isSupported($GLOBALS['dbi']);
        $this->assertFalse($condition);
    }
}
