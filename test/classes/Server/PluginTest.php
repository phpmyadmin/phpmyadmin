<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server;

use PhpMyAdmin\Server\Plugin;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Server\Plugin
 */
class PluginTest extends AbstractTestCase
{
    public function testFromState(): Plugin
    {
        $plugin = Plugin::fromState([
            'name' => 'BLACKHOLE',
            'version' => '1.0',
            'status' => 'ACTIVE',
            'type' => 'STORAGE ENGINE',
            'typeVersion' => '100316.0',
            'library' => 'ha_blackhole.so',
            'libraryVersion' => '1.13',
            'author' => 'MySQL AB',
            'description' => '/dev/null storage engine (anything you write to it disappears)',
            'license' => 'GPL',
            'loadOption' => 'ON',
            'maturity' => 'Stable',
            'authVersion' => '1.0',
        ]);

        self::assertInstanceOf(Plugin::class, $plugin);
        self::assertSame('BLACKHOLE', $plugin->getName());
        self::assertSame('1.0', $plugin->getVersion());
        self::assertSame('ACTIVE', $plugin->getStatus());
        self::assertSame('STORAGE ENGINE', $plugin->getType());
        self::assertSame('100316.0', $plugin->getTypeVersion());
        self::assertSame('ha_blackhole.so', $plugin->getLibrary());
        self::assertSame('1.13', $plugin->getLibraryVersion());
        self::assertSame('MySQL AB', $plugin->getAuthor());
        self::assertSame('GPL', $plugin->getLicense());
        self::assertSame('ON', $plugin->getLoadOption());
        self::assertSame('Stable', $plugin->getMaturity());
        self::assertSame('1.0', $plugin->getAuthVersion());
        self::assertSame('/dev/null storage engine (anything you write to it disappears)', $plugin->getDescription());

        return $plugin;
    }

    /**
     * @param Plugin $plugin Plugin object to be tested
     *
     * @depends testFromState
     */
    public function testToArray(Plugin $plugin): void
    {
        self::assertSame([
            'name' => 'BLACKHOLE',
            'version' => '1.0',
            'status' => 'ACTIVE',
            'type' => 'STORAGE ENGINE',
            'type_version' => '100316.0',
            'library' => 'ha_blackhole.so',
            'library_version' => '1.13',
            'author' => 'MySQL AB',
            'description' => '/dev/null storage engine (anything you write to it disappears)',
            'license' => 'GPL',
            'load_option' => 'ON',
            'maturity' => 'Stable',
            'auth_version' => '1.0',
        ], $plugin->toArray());
    }
}
