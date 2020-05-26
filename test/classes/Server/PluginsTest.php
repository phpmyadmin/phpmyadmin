<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server;

use PhpMyAdmin\Server\Plugin;
use PhpMyAdmin\Server\Plugins;
use PhpMyAdmin\Tests\AbstractTestCase;

class PluginsTest extends AbstractTestCase
{
    /** @var Plugins */
    private $plugins;

    public function testGetAll(): void
    {
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['server'] = 0;

        $this->plugins = new Plugins($GLOBALS['dbi']);

        $plugins = $this->plugins->getAll();

        $this->assertIsArray($plugins);
        $this->assertNotEmpty($plugins);

        $plugin = $plugins[0];

        $this->assertInstanceOf(Plugin::class, $plugin);
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

    public function testGetAllWithoutInformationSchema(): void
    {
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $GLOBALS['server'] = 0;

        $this->plugins = new Plugins($GLOBALS['dbi']);

        $plugins = $this->plugins->getAll();

        $this->assertIsArray($plugins);
        $this->assertNotEmpty($plugins);

        $plugin = $plugins[0];

        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->assertSame([
            'name' => 'partition',
            'version' => null,
            'status' => 'ACTIVE',
            'type' => 'STORAGE ENGINE',
            'type_version' => null,
            'library' => null,
            'library_version' => null,
            'author' => null,
            'description' => null,
            'license' => 'GPL',
            'load_option' => null,
            'maturity' => null,
            'auth_version' => null,
        ], $plugin->toArray());
    }
}
