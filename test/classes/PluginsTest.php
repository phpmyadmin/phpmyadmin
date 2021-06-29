<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Plugins;

/**
 * @covers \PhpMyAdmin\Plugins
 */
class PluginsTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        parent::loadDefaultConfig();
    }

    public function testGetExport(): void
    {
        global $plugin_param;

        $plugins = Plugins::getExport('database', false);
        $this->assertEquals(['export_type' => 'database', 'single_table' => false], $plugin_param);
        $this->assertIsArray($plugins);
        $this->assertCount(14, $plugins);
        $this->assertContainsOnlyInstancesOf(Plugins\ExportPlugin::class, $plugins);
    }

    public function testGetImport(): void
    {
        global $plugin_param;

        $plugins = Plugins::getImport('database');
        $this->assertEquals('database', $plugin_param);
        $this->assertIsArray($plugins);
        $this->assertCount(6, $plugins);
        $this->assertContainsOnlyInstancesOf(Plugins\ImportPlugin::class, $plugins);
    }

    public function testGetSchema(): void
    {
        $plugins = Plugins::getSchema();
        $this->assertIsArray($plugins);
        $this->assertCount(4, $plugins);
        $this->assertContainsOnlyInstancesOf(Plugins\SchemaPlugin::class, $plugins);
    }
}
