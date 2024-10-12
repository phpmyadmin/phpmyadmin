<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Utils;

use PhpMyAdmin\Utils\SessionCache;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpMyAdmin\Utils\SessionCache
 */
class SessionCacheTest extends TestCase
{
    public function testGet(): void
    {
        global $cfg, $server;

        $_SESSION = [];
        $cfg['Server']['user'] = null;
        $server = 'server';

        SessionCache::set('test_data', 5);
        SessionCache::set('test_data_2', 5);

        self::assertNotNull(SessionCache::get('test_data'));
        self::assertNotNull(SessionCache::get('test_data_2'));
        self::assertNull(SessionCache::get('fake_data_2'));
    }

    public function testRemove(): void
    {
        global $cfg, $server;

        $_SESSION = [];
        $cfg['Server']['user'] = null;
        $server = 'server';

        SessionCache::set('test_data', 25);
        SessionCache::set('test_data_2', 25);

        SessionCache::remove('test_data');
        self::assertArrayNotHasKey('test_data', $_SESSION['cache']['server_server']);
        SessionCache::remove('test_data_2');
        self::assertArrayNotHasKey('test_data_2', $_SESSION['cache']['server_server']);
    }

    public function testSet(): void
    {
        global $cfg, $server;

        $_SESSION = [];
        $cfg['Server']['user'] = null;
        $server = 'server';

        SessionCache::set('test_data', 25);
        SessionCache::set('test_data', 5);
        self::assertSame(5, $_SESSION['cache']['server_server']['test_data']);
        SessionCache::set('test_data_3', 3);
        self::assertSame(3, $_SESSION['cache']['server_server']['test_data_3']);
    }

    public function testHas(): void
    {
        global $cfg, $server;

        $_SESSION = [];
        $cfg['Server']['user'] = null;
        $server = 'server';

        SessionCache::set('test_data', 5);
        SessionCache::set('test_data_2', 5);
        SessionCache::set('test_data_3', false);
        SessionCache::set('test_data_4', true);

        self::assertTrue(SessionCache::has('test_data'));
        self::assertTrue(SessionCache::has('test_data_2'));
        self::assertTrue(SessionCache::has('test_data_3'));
        self::assertTrue(SessionCache::has('test_data_4'));
        self::assertFalse(SessionCache::has('fake_data_2'));
    }

    public function testKeyWithoutUser(): void
    {
        global $cfg, $server;

        $_SESSION = [];
        $cfg['Server']['user'] = null;
        $server = 123;

        SessionCache::set('test_data', 5);
        self::assertArrayHasKey('cache', $_SESSION);
        self::assertIsArray($_SESSION['cache']);
        self::assertArrayHasKey('server_123', $_SESSION['cache']);
        self::assertIsArray($_SESSION['cache']['server_123']);
        self::assertArrayHasKey('test_data', $_SESSION['cache']['server_123']);
        self::assertSame(5, $_SESSION['cache']['server_123']['test_data']);
    }

    public function testKeyWithUser(): void
    {
        global $cfg, $server;

        $_SESSION = [];
        $cfg['Server']['user'] = 'test_user';
        $server = 123;

        SessionCache::set('test_data', 5);
        self::assertArrayHasKey('cache', $_SESSION);
        self::assertIsArray($_SESSION['cache']);
        self::assertArrayHasKey('server_123_test_user', $_SESSION['cache']);
        self::assertIsArray($_SESSION['cache']['server_123_test_user']);
        self::assertArrayHasKey('test_data', $_SESSION['cache']['server_123_test_user']);
        self::assertSame(5, $_SESSION['cache']['server_123_test_user']['test_data']);
    }
}
