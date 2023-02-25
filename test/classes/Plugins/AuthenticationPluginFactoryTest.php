<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins;

use PhpMyAdmin\Exceptions\AuthenticationPluginException;
use PhpMyAdmin\Plugins\Auth\AuthenticationConfig;
use PhpMyAdmin\Plugins\Auth\AuthenticationCookie;
use PhpMyAdmin\Plugins\Auth\AuthenticationHttp;
use PhpMyAdmin\Plugins\Auth\AuthenticationSignon;
use PhpMyAdmin\Plugins\AuthenticationPluginFactory;
use PhpMyAdmin\Tests\AbstractTestCase;

/** @covers \PhpMyAdmin\Plugins\AuthenticationPluginFactory */
class AuthenticationPluginFactoryTest extends AbstractTestCase
{
    /**
     * @param non-empty-string $type
     * @param class-string     $class
     *
     * @dataProvider providerForTestValidPlugins
     */
    public function testValidPlugins(string $type, string $class): void
    {
        $GLOBALS['cfg']['Server']['auth_type'] = $type;
        $plugin = (new AuthenticationPluginFactory())->create();
        $this->assertInstanceOf($class, $plugin);
    }

    /** @return iterable<string, array{non-empty-string, class-string}> */
    public static function providerForTestValidPlugins(): iterable
    {
        yield 'config plugin' => ['config', AuthenticationConfig::class];
        yield 'cookie plugin' => ['Cookie', AuthenticationCookie::class];
        yield 'http plugin' => ['HTTP', AuthenticationHttp::class];
        yield 'sign on plugin' => ['signOn', AuthenticationSignon::class];
    }

    public function testInvalidPlugin(): void
    {
        $GLOBALS['cfg']['Server']['auth_type'] = 'invalid';
        $this->expectException(AuthenticationPluginException::class);
        $this->expectExceptionMessage('Invalid authentication method set in configuration: invalid');
        (new AuthenticationPluginFactory())->create();
    }

    public function testSameInstance(): void
    {
        $GLOBALS['cfg']['Server']['auth_type'] = 'cookie';
        $factory = new AuthenticationPluginFactory();
        $firstInstance = $factory->create();
        $secondInstance = (new AuthenticationPluginFactory())->create();
        $this->assertNotSame($firstInstance, $secondInstance);
        $thirdInstance = $factory->create();
        $this->assertSame($firstInstance, $thirdInstance);
        $GLOBALS['cfg']['Server']['auth_type'] = 'config';
        $forthInstance = $factory->create();
        $this->assertSame($firstInstance, $forthInstance);
    }
}
