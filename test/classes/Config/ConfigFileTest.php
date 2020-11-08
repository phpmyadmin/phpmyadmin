<?php
/**
 * Tests for Config File Management
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Tests\AbstractTestCase;
use stdClass;
use function array_keys;
use function count;

/**
 * Tests for Config File Management
 */
class ConfigFileTest extends AbstractTestCase
{
    /**
     * Any valid key that exists in config.default.php and isn't empty
     */
    public const SIMPLE_KEY_WITH_DEFAULT_VALUE = 'DefaultQueryTable';

    /**
     * Object under test
     *
     * @var ConfigFile
     */
    protected $object;

    /**
     * Setup function for test cases
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::loadDefaultConfig();
        $GLOBALS['server'] = 1;
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
        $this->assertEquals(
            [],
            $this->object->getConfig()
        );

        // Check environment state
        $this->assertEquals(
            [],
            $_SESSION['ConfigFile1']
        );

        // Validate default value used in tests
        $default_value = $this->object->getDefault(
            self::SIMPLE_KEY_WITH_DEFAULT_VALUE
        );
        $this->assertNotNull($default_value);
    }

    /**
     * Test for ConfigFile::setPersistKeys()
     */
    public function testPersistentKeys(): void
    {
        $default_simple_value = $this->object->getDefault(
            self::SIMPLE_KEY_WITH_DEFAULT_VALUE
        );
        $default_host = $this->object->getDefault('Servers/1/host');
        $default_config = [
            self::SIMPLE_KEY_WITH_DEFAULT_VALUE => $default_simple_value,
            'Servers/1/host' => $default_host,
            'Servers/2/host' => $default_host,
        ];

        /**
         * Case 1: set default value, key should not be persisted
         */
        $this->object->set(
            self::SIMPLE_KEY_WITH_DEFAULT_VALUE,
            $default_simple_value
        );
        $this->object->set('Servers/1/host', $default_host);
        $this->object->set('Servers/2/host', $default_host);
        $this->assertEmpty($this->object->getConfig());

        /**
         * Case 2: persistent keys should be always present in flat array,
         * even if not explicitly set (unless they are Server entries)
         */
        $this->object->setPersistKeys(array_keys($default_config));
        $this->object->resetConfigData();
        $this->assertEmpty($this->object->getConfig());
        $this->assertEquals(
            $default_config,
            $this->object->getConfigArray()
        );

        /**
         * Case 3: persistent keys should be always saved,
         * even if set to default values
         */
        $this->object->set('Servers/2/host', $default_host);
        $this->assertEquals(
            ['Servers' => [2 => ['host' => $default_host]]],
            $this->object->getConfig()
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

        $this->assertEquals(
            [
                'a' => 1,
                'c' => 3,
            ],
            $this->object->getConfig()
        );

        /**
         * Case 2: disabling filter should allow to set b
         */
        $this->object->setAllowedKeys(null);
        $this->object->set('b', 2);

        $this->assertEquals(
            [
                'a' => 1,
                'b' => 2,
                'c' => 3,
            ],
            $this->object->getConfig()
        );
    }

    /**
     * Test for ConfigFile::setCfgUpdateReadMapping
     */
    public function testConfigReadMapping(): void
    {
        $this->object->setCfgUpdateReadMapping(
            [
                'Servers/value1' => 'Servers/1/value1',
                'Servers/value2' => 'Servers/1/value2',
            ]
        );
        $this->object->set('Servers/1/passthrough1', 1);
        $this->object->set('Servers/1/passthrough2', 2);
        $this->object->updateWithGlobalConfig(['Servers/value1' => 3]);

        $this->assertEquals(
            [
                'Servers' => [
                    1 => [
                        'passthrough1' => 1,
                        'passthrough2' => 2,
                        'value1' => 3,
                    ],
                ],
            ],
            $this->object->getConfig()
        );
        $this->assertEquals(
            3,
            $this->object->get('Servers/1/value1')
        );
    }

    /**
     * Test for ConfigFile::resetConfigData
     */
    public function testResetConfigData(): void
    {
        $this->object->set('key', 'value');

        $this->object->resetConfigData();

        $this->assertEmpty($this->object->getConfig());
        $this->assertEmpty($this->object->getConfigArray());
    }

    /**
     * Test for ConfigFile::setConfigData
     */
    public function testSetConfigData(): void
    {
        $this->object->set('abc', 'should be deleted by setConfigData');
        $this->object->setConfigData(['a' => 'b']);

        $this->assertEquals(
            ['a' => 'b'],
            $this->object->getConfig()
        );
        $this->assertEquals(
            ['a' => 'b'],
            $this->object->getConfigArray()
        );
    }

    /**
     * Test for ConfigFile::set and ConfigFile::get
     */
    public function testBasicSetUsage(): void
    {
        $default_host = $this->object->getDefault('Servers/1/host');
        $nondefault_host = $default_host . '.abc';

        $this->object->set('Servers/4/host', $nondefault_host);
        $this->object->set('Servers/5/host', $default_host);
        $this->object->set('Servers/6/host', $default_host, 'Servers/6/host');
        $this->assertEquals(
            $nondefault_host,
            $this->object->get('Servers/4/host')
        );
        $this->assertEquals(
            null,
            $this->object->get('Servers/5/host')
        );
        $this->assertEquals(
            $default_host,
            $this->object->get('Servers/6/host')
        );

        // return default value for nonexistent keys
        $this->assertNull(
            $this->object->get('key not excist')
        );
        $this->assertEquals(
            [1],
            $this->object->get('key not excist', [1])
        );
        $default = new stdClass();
        $this->assertInstanceOf(
            'stdClass',
            $this->object->get('key not excist', $default)
        );
    }

    /**
     * Test for ConfigFile::set - in PMA Setup
     */
    public function testConfigFileSetInSetup(): void
    {
        $default_value = $this->object->getDefault(
            self::SIMPLE_KEY_WITH_DEFAULT_VALUE
        );

        // default values are not written
        $this->object->set(self::SIMPLE_KEY_WITH_DEFAULT_VALUE, $default_value);
        $this->assertEmpty($this->object->getConfig());
    }

    /**
     * Test for ConfigFile::set - in user preferences
     */
    public function testConfigFileSetInUserPreferences(): void
    {
        $default_value = $this->object->getDefault(
            self::SIMPLE_KEY_WITH_DEFAULT_VALUE
        );

        // values are not written when they are the same as in config.inc.php
        $this->object = new ConfigFile(
            [self::SIMPLE_KEY_WITH_DEFAULT_VALUE => $default_value]
        );
        $this->object->set(self::SIMPLE_KEY_WITH_DEFAULT_VALUE, $default_value);
        $this->assertEmpty($this->object->getConfig());

        // but if config.inc.php differs from config.default.php,
        // allow to overwrite with value from config.default.php
        $config_inc_php_value = $default_value . 'suffix';
        $this->object = new ConfigFile(
            [self::SIMPLE_KEY_WITH_DEFAULT_VALUE => $config_inc_php_value]
        );
        $this->object->set(self::SIMPLE_KEY_WITH_DEFAULT_VALUE, $default_value);
        $this->assertEquals(
            [self::SIMPLE_KEY_WITH_DEFAULT_VALUE => $default_value],
            $this->object->getConfig()
        );
    }

    /**
     * Test for ConfigFile::getFlatDefaultConfig
     *
     * @group medium
     */
    public function testGetFlatDefaultConfig(): void
    {
        $flat_default_config = $this->object->getFlatDefaultConfig();

        $default_value = $this->object->getDefault(
            self::SIMPLE_KEY_WITH_DEFAULT_VALUE
        );
        $this->assertEquals(
            $default_value,
            $flat_default_config[self::SIMPLE_KEY_WITH_DEFAULT_VALUE]
        );

        $localhost_value = $this->object->getDefault('Servers/1/host');
        $this->assertEquals(
            $localhost_value,
            $flat_default_config['Servers/1/host']
        );

        $cfg = [];
        include ROOT_PATH . 'libraries/config.default.php';
        // verify that $cfg read from config.default.php is valid
        $this->assertGreaterThanOrEqual(100, count($cfg));
        $this->assertGreaterThanOrEqual(count($cfg), count($flat_default_config));
    }

    /**
     * Test for ConfigFile::updateWithGlobalConfig
     */
    public function testUpdateWithGlobalConfig(): void
    {
        $this->object->set('key', 'value');
        $this->object->set('key2', 'value');
        $this->object->updateWithGlobalConfig(['key' => 'ABC']);

        $this->assertEquals(
            [
                'key' => 'ABC',
                'key2' => 'value',
            ],
            $this->object->getConfig()
        );
    }

    /**
     * Test for ConfigFile::getCanonicalPath
     */
    public function testGetCanonicalPath(): void
    {
        $this->assertEquals(
            'Servers/1/abcd',
            $this->object->getCanonicalPath('Servers/2/abcd')
        );

        $this->assertEquals(
            'Servers/foo/bar',
            $this->object->getCanonicalPath('Servers/foo/bar')
        );
    }

    /**
     * Test for ConfigFile::getDbEntry
     */
    public function testGetDbEntry(): void
    {
        $cfg_db = include ROOT_PATH . 'libraries/config.values.php';
        // verify that $cfg_db read from config.values.php is valid
        $this->assertGreaterThanOrEqual(20, count($cfg_db));

        $this->assertEquals(
            $cfg_db['Servers'][1]['port'],
            $this->object->getDbEntry('Servers/1/port')
        );
        $this->assertNull($this->object->getDbEntry('no such key'));
        $this->assertEquals(
            [1],
            $this->object->getDbEntry('no such key', [1])
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

        $this->assertEquals(
            4,
            $this->object->getServerCount()
        );

        $this->object->removeServer(2);
        $this->object->removeServer(2);

        $this->assertEquals(
            2,
            $this->object->getServerCount()
        );

        $this->assertLessThanOrEqual(
            2,
            $this->object->get('ServerDefault')
        );
        $this->assertEquals(
            [
                'Servers' => [
                    1 => ['x' => 1],
                    2 => ['x' => 4],
                ],
            ],
            $this->object->getConfig()
        );
        $this->assertEquals(
            [
                'Servers/1/x' => 1,
                'Servers/2/x' => 4,
            ],
            $this->object->getConfigArray()
        );
    }

    /**
     * Test for ConfigFile::getServers
     */
    public function testGetServers(): void
    {
        $this->object->set('Servers/1/x', 'a');
        $this->object->set('Servers/2/x', 'b');

        $this->assertEquals(
            [
                1 => ['x' => 'a'],
                2 => ['x' => 'b'],
            ],
            $this->object->getServers()
        );
    }

    /**
     * Test for ConfigFile::getServerDSN
     */
    public function testGetServerDSN(): void
    {
        $this->assertEquals(
            '',
            $this->object->getServerDSN(1)
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
            ]
        );
        $this->assertEquals(
            'mysqli://testUser@example.com:21',
            $this->object->getServerDSN(1)
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
            ]
        );
        $this->assertEquals(
            'mysqli://testUser@123',
            $this->object->getServerDSN(1)
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
            ]
        );
        $this->assertEquals(
            'mysqli://testUser:***@example.com:21',
            $this->object->getServerDSN(1)
        );
    }

    /**
     * Test for ConfigFile::getServerName
     */
    public function testGetServerName(): void
    {
        $this->assertEquals(
            '',
            $this->object->getServerName(1)
        );

        $this->object->set('Servers/1/host', 'example.com');
        $this->assertEquals(
            'example.com',
            $this->object->getServerName(1)
        );

        $this->object->set('Servers/1/verbose', 'testData');
        $this->assertEquals(
            'testData',
            $this->object->getServerName(1)
        );
    }

    /**
     * Test for ConfigFile::getConfigArray
     */
    public function testGetConfigArray(): void
    {
        $this->object->setPersistKeys([self::SIMPLE_KEY_WITH_DEFAULT_VALUE]);
        $this->object->set('Array/test', ['x', 'y']);
        $default_value = $this->object->getDefault(
            self::SIMPLE_KEY_WITH_DEFAULT_VALUE
        );

        $this->assertEquals(
            [
                self::SIMPLE_KEY_WITH_DEFAULT_VALUE => $default_value,
                'Array/test' => [
                    'x',
                    'y',
                ],
            ],
            $this->object->getConfigArray()
        );
    }
}
