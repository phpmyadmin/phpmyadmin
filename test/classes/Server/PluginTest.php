<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server;

use PhpMyAdmin\Server\Plugin;
use PhpMyAdmin\Tests\AbstractTestCase;

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

        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->assertSame('BLACKHOLE', $plugin->getName());
        $this->assertSame('1.0', $plugin->getVersion());
        $this->assertSame('ACTIVE', $plugin->getStatus());
        $this->assertSame('STORAGE ENGINE', $plugin->getType());
        $this->assertSame('100316.0', $plugin->getTypeVersion());
        $this->assertSame('ha_blackhole.so', $plugin->getLibrary());
        $this->assertSame('1.13', $plugin->getLibraryVersion());
        $this->assertSame('MySQL AB', $plugin->getAuthor());
        $this->assertSame('GPL', $plugin->getLicense());
        $this->assertSame('ON', $plugin->getLoadOption());
        $this->assertSame('Stable', $plugin->getMaturity());
        $this->assertSame('1.0', $plugin->getAuthVersion());
        $this->assertSame(
            '/dev/null storage engine (anything you write to it disappears)',
            $plugin->getDescription()
        );

        return $plugin;
    }

    /**
     * @param Plugin $plugin Plugin object to be tested
     *
     * @depends testFromState
     */
    public function testToArray(Plugin $plugin): void
    {
        $this->assertSame([
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
