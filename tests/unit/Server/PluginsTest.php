<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Server\Plugins;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use function __;

#[CoversClass(Plugins::class)]
class PluginsTest extends AbstractTestCase
{
    private Plugins $plugins;

    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    public function testGetAll(): void
    {
        $config = Config::getInstance();
        $config->settings['MaxCharactersInDisplayedSQL'] = 1000;
        $config->selectedServer['DisableIS'] = false;

        $this->plugins = new Plugins(DatabaseInterface::getInstance());

        $plugins = $this->plugins->getAll();

        self::assertNotEmpty($plugins);

        $plugin = $plugins[0];

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

    public function testGetAllWithoutInformationSchema(): void
    {
        $config = Config::getInstance();
        $config->settings['MaxCharactersInDisplayedSQL'] = 1000;
        $config->selectedServer['DisableIS'] = true;

        $this->plugins = new Plugins(DatabaseInterface::getInstance());

        $plugins = $this->plugins->getAll();

        self::assertNotEmpty($plugins);

        $plugin = $plugins[0];

        self::assertSame([
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

    public function testGetAuthentication(): void
    {
        $this->plugins = new Plugins(DatabaseInterface::getInstance());
        $plugins = $this->plugins->getAuthentication();
        self::assertNotEmpty($plugins);
        self::assertSame(
            [
                'mysql_old_password' => __('Old MySQL-4.0 authentication'),
                'mysql_native_password' => __('Native MySQL authentication'),
                'sha256_password' => __('SHA256 password authentication'),
                'caching_sha2_password' => __('Caching sha2 authentication'),
                'auth_socket' => __('Unix Socket based authentication'),
                'unknown_auth_plugin' => 'Unknown authentication',
            ],
            $plugins,
        );
    }
}
