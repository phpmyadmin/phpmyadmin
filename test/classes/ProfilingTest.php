<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Profiling;
use PhpMyAdmin\Util;

class ProfilingTest extends AbstractTestCase
{
    public function testIsSupported(): void
    {
        global $dbi, $server;

        $server = 1;

        Util::cacheSet('profiling_supported', true);
        $condition = Profiling::isSupported($dbi);
        $this->assertTrue($condition);

        Util::cacheSet('profiling_supported', false);
        $condition = Profiling::isSupported($dbi);
        $this->assertFalse($condition);
    }
}
