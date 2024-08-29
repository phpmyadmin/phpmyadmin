<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Settings;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use stdClass;

use function array_keys;
use function count;

#[CoversClass(ConfigFile::class)]
#[Medium]
class ConfigFileTest extends AbstractTestCase
{
    /**
     * Any valid key that exists in {@see \PhpMyAdmin\Config\Settings} and isn't empty
     */
    public const SIMPLE_KEY_WITH_DEFAULT_VALUE = 'DefaultQueryTable';

    /**
     * Object under test
     */
    protected ConfigFile $object;

    /**
     * Setup function for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->object = new ConfigFile();
    }

    /**
     * TearDown function for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->object->setConfigData([]);
        unset($this->object);
    }

    /**
     * Test for new ConfigFile()
     */
    public function testNewObjectState(): void
    {
        // Check default dynamic values
        self::assertSame(
            [],
            $this->object->getConfig(),
        );

        // Check environment state
        self::assertSame(
            [],
            $_SESSION['ConfigFile1'],
        );

        // Validate default value used in tests
        $defaultValue = $this->object->getDefault(self::SIMPLE_KEY_WITH_DEFAULT_VALUE);
        self::assertNotNull($defaultValue);
    }

    /**
     * Test for ConfigFile::setPersistKeys()
     */
    public function testPersistentKeys(): void
    {
        $defaultSimpleValue = $this->object->getDefault(self::SIMPLE_KEY_WITH_DEFAULT_VALUE);
        $defaultHost = $this->object->getDefault('Servers/1/host');
        $defaultConfig = [
            self::SIMPLE_KEY_WITH_DEFAULT_VALUE => $defaultSimpleValue,
            'Servers/1/host' => $defaultHost,
            'Servers/2/host' => $defaultHost,
        ];

        /**
         * Case 1: set default value, key should not be persisted
         */
        $this->object->set(self::SIMPLE_KEY_WITH_DEFAULT_VALUE, $defaultSimpleValue);
        $this->object->set('Servers/1/host', $defaultHost);
        $this->object->set('Servers/2/host', $defaultHost);
        self::assertEmpty($this->object->getConfig());

        /**
         * Case 2: persistent keys should be always present in flat array,
         * even if not explicitly set (unless they are Server entries)
         */
        $this->object->setPersistKeys(array_keys($defaultConfig));
        $this->object->resetConfigData();
        self::assertEmpty($this->object->getConfig());
        self::assertSame(
            $defaultConfig,
            $this->object->getConfigArray(),
        );

        /**
         * Case 3: persistent keys should be always saved,
         * even if set to default values
         */
        $this->object->set('Servers/2/host', $defaultHost);
        self::assertSame(
            ['Servers' => [2 => ['host' => $defaultHost]]],
            $this->object->getConfig(),
        );
    }

    /**
     * Test for ConfigFile::setAllowedKeys
     */
    public function testAllowedKeys(): void
    {
        /**
         * Case 1: filter should not allow to set b
         */
        $this->object->setAllowedKeys(['a', 'c']);
        $this->object->set('a', 1);
        $this->object->set('b', 2);
        $this->object->set('c', 3);

        self::assertSame(
            ['a' => 1, 'c' => 3],
            $this->object->getConfig(),
        );

        /**
         * Case 2: disabling filter should allow to set b
         */
        $this->object->setAllowedKeys(null);
        $this->object->set('b', 2);

        self::assertEquals(
            ['a' => 1, 'b' => 2, 'c' => 3],
            $this->object->getConfig(),
        );
    }

    /**
     * Test for ConfigFile::setCfgUpdateReadMapping
     */
    public function testConfigReadMapping(): void
    {
        $this->object->setCfgUpdateReadMapping(
            ['Servers/value1' => 'Servers/1/value1', 'Servers/value2' => 'Servers/1/value2'],
        );
        $this->object->set('Servers/1/passthrough1', 1);
        $this->object->set('Servers/1/passthrough2', 2);
        $this->object->updateWithGlobalConfig(['Servers/value1' => 3]);

        self::assertSame(
            ['Servers' => [1 => ['passthrough1' => 1, 'passthrough2' => 2, 'value1' => 3]]],
            $this->object->getConfig(),
        );
        self::assertSame(
            3,
            $this->object->get('Servers/1/value1'),
        );
    }

    /**
     * Test for ConfigFile::resetConfigData
     */
    public function testResetConfigData(): void
    {
        $this->object->set('key', 'value');

        $this->object->resetConfigData();

        self::assertEmpty($this->object->getConfig());
        self::assertEmpty($this->object->getConfigArray());
    }

    /**
     * Test for ConfigFile::setConfigData
     */
    public function testSetConfigData(): void
    {
        $this->object->set('abc', 'should be deleted by setConfigData');
        $this->object->setConfigData(['a' => 'b']);

        self::assertSame(
            ['a' => 'b'],
            $this->object->getConfig(),
        );
        self::assertSame(
            ['a' => 'b'],
            $this->object->getConfigArray(),
        );
    }

    /**
     * Test for ConfigFile::set and ConfigFile::get
     */
    public function testBasicSetUsage(): void
    {
        $defaultHost = $this->object->getDefault('Servers/1/host');
        $nonDefaultHost = $defaultHost . '.abc';

        $this->object->set('Servers/4/host', $nonDefaultHost);
        $this->object->set('Servers/5/host', $defaultHost);
        $this->object->set('Servers/6/host', $defaultHost, 'Servers/6/host');
        self::assertSame(
            $nonDefaultHost,
            $this->object->get('Servers/4/host'),
        );
        self::assertNull($this->object->get('Servers/5/host'));
        self::assertSame(
            $defaultHost,
            $this->object->get('Servers/6/host'),
        );

        // return default value for nonexistent keys
        self::assertNull(
            $this->object->get('key not excist'),
        );
        self::assertSame(
            [1],
            $this->object->get('key not excist', [1]),
        );
        $default = new stdClass();
        self::assertInstanceOf(
            stdClass::class,
            $this->object->get('key not excist', $default),
        );
    }

    /**
     * Test for ConfigFile::set - in PMA Setup
     */
    public function testConfigFileSetInSetup(): void
    {
        $defaultValue = $this->object->getDefault(self::SIMPLE_KEY_WITH_DEFAULT_VALUE);

        // default values are not written
        $this->object->set(self::SIMPLE_KEY_WITH_DEFAULT_VALUE, $defaultValue);
        self::assertEmpty($this->object->getConfig());
    }

    /**
     * Test for ConfigFile::set - in user preferences
     */
    public function testConfigFileSetInUserPreferences(): void
    {
        $defaultValue = $this->object->getDefault(self::SIMPLE_KEY_WITH_DEFAULT_VALUE);

        // values are not written when they are the same as in config.inc.php
        $this->object = new ConfigFile(
            [self::SIMPLE_KEY_WITH_DEFAULT_VALUE => $defaultValue],
        );
        $this->object->set(self::SIMPLE_KEY_WITH_DEFAULT_VALUE, $defaultValue);
        self::assertEmpty($this->object->getConfig());

        // but if config.inc.php differs from the default values,
        // allow to overwrite with value from the default values
        $configIncPhpValue = $defaultValue . 'suffix';
        $this->object = new ConfigFile(
            [self::SIMPLE_KEY_WITH_DEFAULT_VALUE => $configIncPhpValue],
        );
        $this->object->set(self::SIMPLE_KEY_WITH_DEFAULT_VALUE, $defaultValue);
        self::assertSame(
            [self::SIMPLE_KEY_WITH_DEFAULT_VALUE => $defaultValue],
            $this->object->getConfig(),
        );
    }

    /**
     * Test for ConfigFile::getFlatDefaultConfig
     */
    public function testGetFlatDefaultConfig(): void
    {
        $flatDefaultConfig = $this->object->getFlatDefaultConfig();

        $defaultValue = $this->object->getDefault(self::SIMPLE_KEY_WITH_DEFAULT_VALUE);
        self::assertSame($defaultValue, $flatDefaultConfig[self::SIMPLE_KEY_WITH_DEFAULT_VALUE]);

        $localhostValue = $this->object->getDefault('Servers/1/host');
        self::assertSame($localhostValue, $flatDefaultConfig['Servers/1/host']);

        $settings = new Settings([]);
        $cfg = $settings->asArray();

        self::assertGreaterThanOrEqual(100, count($cfg));
        self::assertGreaterThanOrEqual(count($cfg), count($flatDefaultConfig));
    }

    /**
     * Test for ConfigFile::updateWithGlobalConfig
     */
    public function testUpdateWithGlobalConfig(): void
    {
        $this->object->set('key', 'value');
        $this->object->set('key2', 'value');
        $this->object->updateWithGlobalConfig(['key' => 'ABC']);

        self::assertSame(
            ['key' => 'ABC', 'key2' => 'value'],
            $this->object->getConfig(),
        );
    }

    /**
     * Test for ConfigFile::getCanonicalPath
     */
    public function testGetCanonicalPath(): void
    {
        self::assertSame(
            'Servers/1/abcd',
            $this->object->getCanonicalPath('Servers/2/abcd'),
        );

        self::assertSame(
            'Servers/foo/bar',
            $this->object->getCanonicalPath('Servers/foo/bar'),
        );
    }

    /**
     * Test for ConfigFile::getDbEntry
     */
    public function testGetDbEntry(): void
    {
        $cfgDb = $this->object->getAllowedValues();
        // verify that $cfg_db read from config.values.php is valid
        self::assertGreaterThanOrEqual(20, count($cfgDb));

        self::assertSame(
            $cfgDb['Servers'][1]['port'],
            $this->object->getDbEntry('Servers/1/port'),
        );
        self::assertNull($this->object->getDbEntry('no such key'));
        self::assertSame(
            [1],
            $this->object->getDbEntry('no such key', [1]),
        );
    }

    /**
     * Test for ConfigFile::getServerCount
     */
    public function testGetServerCount(): void
    {
        $this->object->set('Servers/1/x', 1);
        $this->object->set('Servers/2/x', 2);
        $this->object->set('Servers/3/x', 3);
        $this->object->set('Servers/4/x', 4);
        $this->object->set('ServerDefault', 3);

        self::assertSame(
            4,
            $this->object->getServerCount(),
        );

        $this->object->removeServer(2);
        $this->object->removeServer(2);

        self::assertSame(
            2,
            $this->object->getServerCount(),
        );

        self::assertLessThanOrEqual(
            2,
            $this->object->get('ServerDefault'),
        );
        self::assertSame(
            ['Servers' => [1 => ['x' => 1], 2 => ['x' => 4]]],
            $this->object->getConfig(),
        );
        self::assertSame(
            ['Servers/1/x' => 1, 'Servers/2/x' => 4],
            $this->object->getConfigArray(),
        );
    }

    /**
     * Test for ConfigFile::getServers
     */
    public function testGetServers(): void
    {
        $this->object->set('Servers/1/x', 'a');
        $this->object->set('Servers/2/x', 'b');

        self::assertSame(
            [1 => ['x' => 'a'], 2 => ['x' => 'b']],
            $this->object->getServers(),
        );
    }

    /**
     * Test for ConfigFile::getServerDSN
     */
    public function testGetServerDSN(): void
    {
        self::assertSame(
            '',
            $this->object->getServerDSN(1),
        );

        $this->object->updateWithGlobalConfig(
            [
                'Servers' => [
                    1 => [
                        'auth_type' => 'config',
                        'user' => 'testUser',
                        'host' => 'example.com',
                        'port' => '21',
                    ],
                ],
            ],
        );
        self::assertSame(
            'mysqli://testUser@example.com:21',
            $this->object->getServerDSN(1),
        );

        $this->object->updateWithGlobalConfig(
            [
                'Servers' => [
                    1 => [
                        'auth_type' => 'config',
                        'user' => 'testUser',
                        'host' => 'localhost',
                        'port' => '21',
                        'socket' => '123',
                        'password' => '',
                    ],
                ],
            ],
        );
        self::assertSame(
            'mysqli://testUser@123',
            $this->object->getServerDSN(1),
        );

        $this->object->updateWithGlobalConfig(
            [
                'Servers' => [
                    1 => [
                        'auth_type' => 'config',
                        'user' => 'testUser',
                        'host' => 'example.com',
                        'port' => '21',
                        'password' => 'testPass',
                    ],
                ],
            ],
        );
        self::assertSame(
            'mysqli://testUser:***@example.com:21',
            $this->object->getServerDSN(1),
        );
    }

    /**
     * Test for ConfigFile::getServerName
     */
    public function testGetServerName(): void
    {
        self::assertSame(
            '',
            $this->object->getServerName(1),
        );

        $this->object->set('Servers/1/host', 'example.com');
        self::assertSame(
            'example.com',
            $this->object->getServerName(1),
        );

        $this->object->set('Servers/1/verbose', 'testData');
        self::assertSame(
            'testData',
            $this->object->getServerName(1),
        );
    }

    /**
     * Test for ConfigFile::getConfigArray
     */
    public function testGetConfigArray(): void
    {
        $this->object->setPersistKeys([self::SIMPLE_KEY_WITH_DEFAULT_VALUE]);
        $this->object->set('Array/test', ['x', 'y']);
        $defaultValue = $this->object->getDefault(self::SIMPLE_KEY_WITH_DEFAULT_VALUE);

        self::assertEquals(
            [self::SIMPLE_KEY_WITH_DEFAULT_VALUE => $defaultValue, 'Array/test' => ['x', 'y']],
            $this->object->getConfigArray(),
        );
    }
}
