<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Utils;

use PhpMyAdmin\Utils\SessionCache;
use PHPUnit\Framework\TestCase;

class SessionCacheTest extends TestCase
{
    public function testGet(): void
    {
        global $server;

        $server = 'server';

        SessionCache::set('test_data', 5);
        SessionCache::set('test_data_2', 5);

        $this->assertNotNull(SessionCache::get('test_data'));
        $this->assertNotNull(SessionCache::get('test_data_2'));
        $this->assertNull(SessionCache::get('fake_data_2'));
    }

    public function testRemove(): void
    {
        global $server;

        $server = 'server';

        SessionCache::set('test_data', 25);
        SessionCache::set('test_data_2', 25);

        SessionCache::remove('test_data');
        $this->assertArrayNotHasKey(
            'test_data',
            $_SESSION['cache']['server_server']
        );
        SessionCache::remove('test_data_2');
        $this->assertArrayNotHasKey(
            'test_data_2',
            $_SESSION['cache']['server_server']
        );
    }

    public function testSet(): void
    {
        global $server;

        $server = 'server';

        SessionCache::set('test_data', 25);
        SessionCache::set('test_data', 5);
        $this->assertEquals(5, $_SESSION['cache']['server_server']['test_data']);
        SessionCache::set('test_data_3', 3);
        $this->assertEquals(3, $_SESSION['cache']['server_server']['test_data_3']);
    }

    public function testHas(): void
    {
        global $server;

        $server = 'server';

        SessionCache::set('test_data', 5);
        SessionCache::set('test_data_2', 5);

        $this->assertTrue(SessionCache::has('test_data'));
        $this->assertTrue(SessionCache::has('test_data_2'));
        $this->assertFalse(SessionCache::has('fake_data_2'));
    }
}
