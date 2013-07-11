<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Config File Management
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/config/ConfigFile.class.php';
require_once 'libraries/php-gettext/gettext.inc';

/**
 * Tests for Config File Management
 *
 * @package PhpMyAdmin-test
 */
class PMA_ConfigFile_Test extends PHPUnit_Framework_TestCase
{
    protected $object;

    /**
     * Setup function for test cases
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['cfg']['AvailableCharsets'] = array();
        $GLOBALS['server'] = 1;
        $this->object = ConfigFile::getInstance();
    }

    /**
     * TearDown function for test cases
     *
     * @return void
     */
    protected function tearDown()
    {

        unset($_SESSION[$this->readAttribute($this->object, "_id")]);
        unset($this->object);
    }

    /**
     * Test for new ConfigFile()
     *
     * @return void
     * @test
     */
    public function testConfigFileConstructor()
    {   
        $attr_instance = new ReflectionProperty("ConfigFile", "_instance");
        $attr_instance->setAccessible(true);
        $attr_instance->setValue(null, null);
        $this->object = ConfigFile::getInstance();
        $cfg = $this->readAttribute($this->object, '_cfg');

        $this->assertEquals(
            "82%",
            $cfg['fontsize']
        );

        $this->assertInstanceOf(
            "PMA_Config",
            $this->readAttribute($this->object, '_orgCfgObject')
        );

        if (extension_loaded('mysqli')) {
            $expect = "mysqli";
        } else {
            $expect = "mysql";
        }

        $this->assertEquals(
            $expect,
            $cfg['Servers'][1]['extension']
        );

        $this->assertEquals(
            array(),
            $_SESSION["ConfigFile1"]
        );

        $this->assertAttributeEquals(
            "ConfigFile1",
            "_id",
            $this->object
        );

    }

    /**
     * Test for ConfigFile::getInstance()
     *
     * @return void
     * @test
     */
    public function testGetInstance()
    {
        $this->assertInstanceOf(
            "ConfigFile",
            $this->object
        );
    }

    /**
     * Test for ConfigFile::getOrgConfigObj()
     *
     * @return void
     * @test
     */
    public function testGetOrgConfigObj()
    {
        $this->assertEquals(
            $this->readAttribute($this->object, '_orgCfgObject'),
            $this->object->getOrgConfigObj()
        );
    }

    /**
     * Test for ConfigFile::setPersistKeys()
     *
     * @return void
     * @test
     */
    public function testSetPersistKeys()
    {
        $this->object->setPersistKeys(array("a" => 1, "b" => 1, "c" => 2));
        $this->assertEquals(
            array("1" => "b", "2" => "c"),
            $this->readAttribute($this->object, '_persistKeys')
        );
    }

    /**
     * Test for ConfigFile::getPersistKeysMap
     *
     * @return void
     * @test
     */
    public function testGetPersistKeysMap()
    {
        $this->assertEquals(
            $this->readAttribute($this->object, '_persistKeys'),
            $this->object->getPersistKeysMap()
        );
    }

    /**
     * Test for ConfigFile::setAllowedKeys
     *
     * @return void
     * @test
     */
    public function testSetAllowedKeys()
    {
        $this->object->setAllowedKeys(array("a" => 1, "c" => 2));
        $this->assertEquals(
            array("1" => "a", "2" => "c"),
            $this->readAttribute($this->object, '_setFilter')
        );

        $this->object->setAllowedKeys(null);
        $this->assertEquals(
            null,
            $this->readAttribute($this->object, '_setFilter')
        );
    }

    /**
     * Test for ConfigFile::setCfgUpdateReadMapping
     *
     * @return void
     * @test
     */
    public function testSetCfgUpdateReadMapping()
    {
        $this->object->setCfgUpdateReadMapping(array(1, 2, 3));
        $this->assertEquals(
            array(1, 2, 3),
            $this->readAttribute($this->object, '_cfgUpdateReadMapping')
        );
    }

    /**
     * Test for ConfigFile::resetConfigData
     *
     * @return void
     * @test
     */
    public function testResetConfigData()
    {
        $selfid = $this->readAttribute($this->object, '_id');
        $_SESSION[$selfid] = "foo";

        $this->object->resetConfigData();

        $this->assertEquals(
            array(),
            $_SESSION[$selfid]
        );
    }

    /**
     * Test for ConfigFile::setConfigData
     *
     * @return void
     * @test
     */
    public function testSetConfigData()
    {
        $selfid = $this->readAttribute($this->object, '_id');

        $this->object->setConfigData(array("foo"));

        $this->assertEquals(
            array("foo"),
            $_SESSION[$selfid]
        );
    }

    /**
     * Test for ConfigFile::set
     *
     * @return void
     * @test
     */
    public function testConfigFileSet()
    {
        if (!function_exists("runkit_constant_redefine")) {
            $this->markTestSkipped("Cannot redefine constant");
        }

        $reflection = new \ReflectionClass("ConfigFile");

        $attrSetFilter = $reflection->getProperty("_setFilter");
        $attrSetFilter->setAccessible(true);

        $attrCfg = $reflection->getProperty('_cfg');
        $attrCfg->setAccessible(true);

        $attrCfgObject = $reflection->getProperty('_orgCfgObject');
        $attrCfgObject->setAccessible(true);

        $attrConfigProperty = new \ReflectionProperty("PMA_Config", "settings");
        $attrConfigProperty->setAccessible(true);

        /**
         * Case 1
         */
        $attrSetFilter->setValue($this->object, array());

        $this->assertNull(
            $this->object->set("a", "b")
        );

        /**
         * Case 2
         */
        $this->object->setPersistKeys(array("Servers/1/test" => 1));
        $attrSetFilter->setValue($this->object, array("Servers/1/test" => 1));
        $this->object->set("Servers/42/test", "val");

        $expectedArr = array("Servers" => array("42" => array("test" => "val")));

        $this->assertEquals(
            $expectedArr,
            $_SESSION[$this->readAttribute($this->object, "_id")]
        );

        $expectedArr = array("Servers" => array("42" => "val"));

        /**
         * Case 3
         */
        $attrSetFilter->setValue($this->object, array("Servers/42" => 1));
        $attrCfg->setValue($this->object, $expectedArr);
        $attrConfigProperty->setValue(
            $attrCfgObject->getValue($this->object),
            array()
        );

        $pma_setup = null;

        if (!defined('PMA_SETUP')) {
            define('PMA_SETUP', true);
        } else {
            $pma_setup = PMA_SETUP;
            runkit_constant_redefine('PMA_SETUP', true);
        }

        $this->object->setPersistKeys(array("Servers/42" => 1));
        $this->object->set("Servers/42", "val");

        $this->assertEquals(
            array(),
            $_SESSION[$this->readAttribute($this->object, "_id")]
        );

        /**
         * Case 4
         */
        $attrConfigProperty->setValue(
            $attrCfgObject->getValue($this->object),
            $expectedArr
        );

        $this->object->set("Servers/42", "val");
        $this->assertEquals(
            array(),
            $_SESSION[$this->readAttribute($this->object, "_id")]
        );

        /**
         * Case 5
         */
        $attrCfg->setValue($this->object, array());

        $this->object->set("Servers/42", "");
        $this->assertEquals(
            array(),
            $_SESSION[$this->readAttribute($this->object, "_id")]
        );

        /**
         * Case 6
         */
        $this->object->set("Servers/42", "foobar");
        runkit_constant_redefine('PMA_SETUP', false);

        $this->assertEquals(
            array("Servers" => array("42" => "foobar")),
            $_SESSION[$this->readAttribute($this->object, "_id")]
        );

        if ($pma_setup) {
            runkit_constant_redefine("PMA_SETUP", $pma_setup);
        } else {
            runkit_constant_remove("PMA_SETUP");
        }
    }

    /**
     * Test for ConfigFile::_flattenArray
     *
     * @return void
     * @test
     */
    public function testFlattenArray()
    {
        $method = new \ReflectionMethod("ConfigFile", "_flattenArray");
        $method->setAccessible(true);

        $method->invoke(
            $this->object,
            array(
                "one" => array("foo" => "bar"),
                "two" => array(1, 2, 3),
                "three" => 3
            ),
            "one",
            "foobar"
        );

        $expectArr = array(
            "foobarone/one/foo" => "bar",
            "foobarone/two" => array(1, 2, 3),
            "foobarone/three" => 3
        );

        $this->assertEquals(
            $expectArr,
            $this->readAttribute($this->object, "_flattenArrayResult")
        );
    }

    /**
     * Test for ConfigFile::getFlatDefaultConfig
     *
     * @return void
     * @test
     */
    public function testGetFlatDefaultConfig()
    {
        $attrCfg = new \ReflectionProperty('ConfigFile', '_cfg');
        $attrCfg->setAccessible(true);
        $attrCfg->setValue(
            $this->object,
            array(
                "one" => array("foo" => "bar"),
                "two" => array(1, 2, 3),
                "three" => 3
            )
        );

        $expectArr = array(
            "one/foo" => "bar",
            "two" => array(1, 2, 3),
            "three" => 3
        );

        $this->assertEquals(
            $expectArr,
            $this->object->getFlatDefaultConfig()
        );
    }

    /**
     * Test for ConfigFile::updateWithGlobalConfig
     *
     * @return void
     * @test
     */
    public function testUpdateWithGlobalConfig()
    {
        $this->object = $this->getMockBuilder('ConfigFile')
            ->setMethods(array("set"))
            ->disableOriginalConstructor()
            ->getMock();

        $reflection = new \ReflectionClass('ConfigFile');

        $attrReadMapping = $reflection->getProperty('_cfgUpdateReadMapping');
        $attrReadMapping->setAccessible(true);
        $attrReadMapping->setValue(
            $this->object,
            array("one/foo" => "one/foobar")
        );


        $this->object
            ->expects($this->at(0))
            ->method('set')
            ->with("one/foobar", "bar", "one/foobar");

        $this->object
            ->expects($this->at(1))
            ->method('set')
            ->with("two", array(1, 2, 3), "two");

        $this->object->updateWithGlobalConfig(
            array(
                "one" => array("foo" => "bar"),
                "two" => array(1, 2, 3)
            )
        );
    }

    /**
     * Test for ConfigFile::get
     *
     * @return void
     * @test
     */
    public function testConfigFileGet()
    {
        $_SESSION[$this->readAttribute($this->object, "_id")] = array(
            "1" => array("2" => "val")
        );

        $this->assertEquals(
            "val",
            $this->object->get("1/2")
        );

        $_SESSION[$this->readAttribute($this->object, "_id")] = array(
            "1" => array()
        );

        $this->assertEquals(
            "foobar",
            $this->object->get("1/2", "foobar")
        );
    }

    /**
     * Test for ConfigFile::getValue
     *
     * @return void
     * @test
     */
    public function testGetValue()
    {
        $_SESSION[$this->readAttribute($this->object, "_id")] = array(
            "Servers" => array("2" => "val")
        );

        $this->assertEquals(
            "val",
            $this->object->getValue("Servers/2")
        );

        $_SESSION[$this->readAttribute($this->object, "_id")] = array();

        $reflection = new \ReflectionClass('ConfigFile');
        $attrCfg = $reflection->getProperty('_cfg');
        $attrCfg->setAccessible(true);
        $attrCfg->setValue(
            $this->object,
            array(
                "Servers" => array("1" => array("test" => "val"))
            )
        );

        $this->assertEquals(
            "val",
            $this->object->getValue("Servers/2/test")
        );
    }

    /**
     * Test for ConfigFile::getDefault
     *
     * @return void
     * @test
     */
    public function testGetDefault()
    {

        $reflection = new \ReflectionClass('ConfigFile');
        $attrCfg = $reflection->getProperty('_cfg');
        $attrCfg->setAccessible(true);
        $attrCfg->setValue(
            $this->object,
            array(
                "Servers" => array("1" => array("test" => "val"))
            )
        );
        $this->assertEquals(
            "val",
            $this->object->getDefault("Servers/1/test")
        );

        $attrCfg->setValue(
            $this->object,
            array(
                "Servers" => array()
            )
        );
        $this->assertEquals(
            "val",
            $this->object->getDefault("Servers/1/test", "val")
        );
    }

    /**
     * Test for ConfigFile::getCanonicalPath
     *
     * @return void
     * @test
     */
    public function testGetCanonicalPath()
    {
        $this->assertEquals(
            "Servers/1/abcd",
            $this->object->getCanonicalPath("Servers/2/abcd")
        );

        $this->assertEquals(
            "Servers/foo/bar",
            $this->object->getCanonicalPath("Servers/foo/bar")
        );
    }

    /**
     * Test for ConfigFile::getDbEntry
     *
     * @return void
     * @test
     */
    public function testGetDbEntry()
    {
        $reflection = new \ReflectionClass('ConfigFile');
        $attrCfg = $reflection->getProperty('_cfgDb');
        $attrCfg->setAccessible(true);
        $attrCfg->setValue(
            $this->object,
            array(
                "Servers" => array("1" => array("test" => "val"))
            )
        );
        $this->assertEquals(
            "val",
            $this->object->getDbEntry("Servers/1/test")
        );

        $attrCfg->setValue(
            $this->object,
            array(
                "Servers" => array()
            )
        );
        $this->assertEquals(
            "val",
            $this->object->getDbEntry("Servers/1/test", "val")
        );
    }

    /**
     * Test for ConfigFile::getServerCount
     *
     * @return void
     * @test
     */
    public function testGetServerCount()
    {
        $_SESSION[$this->readAttribute($this->object, '_id')]['Servers'] = array(
            1, 2, 3, 4
        );

        $this->assertEquals(
            4,
            $this->object->getServerCount()
        );

        unset($_SESSION[$this->readAttribute($this->object, '_id')]['Servers']);

        $this->assertEquals(
            0,
            $this->object->getServerCount()
        );
    }

    /**
     * Test for ConfigFile::getServers
     *
     * @return void
     * @test
     */
    public function testGetServers()
    {
        $_SESSION[$this->readAttribute($this->object, '_id')]['Servers'] = array(
            1, 2, 3, 4
        );

        $this->assertEquals(
            array(1, 2, 3, 4),
            $this->object->getServers()
        );

        unset($_SESSION[$this->readAttribute($this->object, '_id')]['Servers']);

        $this->assertEquals(
            null,
            $this->object->getServerCount()
        );
    }

    /**
     * Test for ConfigFile::getServerDSN
     *
     * @return void
     * @test
     */
    public function testGetServerDSN()
    {
        $this->assertEquals(
            '',
            $this->object->getServerDSN('foobar123')
        );

        $objectID = $this->readAttribute($this->object, "_id");
        $_SESSION[$objectID]['Servers']['foobar123'] = array(
            "extension" => "mysqli",
            "auth_type" => "config",
            "user" => "testUser",
            "connect_type" => "tcp",
            "host" => "example.com",
            "port" => "21"
        );

        $this->assertEquals(
            "mysqli://testUser:***@example.com:21",
            $this->object->getServerDSN("foobar123")
        );

        $_SESSION[$objectID]['Servers']['foobar123'] = array(
            "extension" => "mysqli",
            "auth_type" => "config",
            "user" => "testUser",
            "connect_type" => "ssh",
            "host" => "example.com",
            "port" => "21",
            "nopassword" => "yes",
            "socket" => "123"
        );

        $this->assertEquals(
            "mysqli://testUser@123",
            $this->object->getServerDSN("foobar123")
        );
    }

    /**
     * Test for ConfigFile::getServerName
     *
     * @return void
     * @test
     */
    public function testGetServerName()
    {
        $this->assertEquals(
            '',
            $this->object->getServerName('foobar123')
        );

        $objectID = $this->readAttribute($this->object, "_id");
        $_SESSION[$objectID]['Servers']['foobar123'] = array(
            "verbose" => "testData"
        );

        $this->assertEquals(
            "testData",
            $this->object->getServerName("foobar123")
        );

        $_SESSION[$objectID]['Servers']['foobar123'] = array(
            "host" => "example.com"
        );

        $this->assertEquals(
            "example.com",
            $this->object->getServerName("foobar123")
        );

        $_SESSION[$objectID]['Servers']['foobar123'] = array(
            "host" => ""
        );

        $this->assertEquals(
            "localhost",
            $this->object->getServerName("foobar123")
        );
    }

    /**
     * Test for ConfigFile::removeServer
     *
     * @return void
     * @test
     */
    public function testRemoveServer()
    {
        $this->assertEquals(
            null,
            $this->object->removeServer(1)
        );

        $objectID = $this->readAttribute($this->object, "_id");
        $_SESSION[$objectID]['Servers'] = array(
            "1" => array(),
            "2" => array("test" => "val"),
            "3" => array()
        );

        $this->object->removeServer(2);

        $this->assertEquals(
            array(
                "1" => array(),
                "2" => array()
            ),
            $_SESSION[$objectID]['Servers']
        );

        $_SESSION[$objectID]['Servers'] = array(
            "1" => array()
        );

        $this->object->removeServer(1);

        $this->assertEmpty(
            $_SESSION[$objectID]['Servers']
        );

        $_SESSION[$objectID]['Servers'] = array(
            "1" => array(),
            "2" => array("test" => "val"),
            "3" => array()
        );

        $_SESSION[$objectID]['ServerDefault'] = 5;

        $this->object->removeServer(3);

        $this->assertEquals(
            array(
                "1" => array(),
                "2" => array("test" => "val")
            ),
            $_SESSION[$objectID]['Servers']
        );

        if (isset($_SESSION[$objectID]['ServerDefault'])) {
            $success = false;
        } else {
            $success = true;
        }
        $this->assertTrue(
            $success
        );
    }

    /**
     * Test for ConfigFile::getFilePath
     *
     * @return void
     * @test
     */
    public function testGetFilePath()
    {
        $result = $this->object->getFilePath();

        $this->assertEquals(
            SETUP_CONFIG_FILE,
            $result
        );
    }

    /**
     * Test for ConfigFile::getConfig
     *
     * @return void
     * @test
     */
    public function testGetConfig()
    {
        $objectID = $this->readAttribute($this->object, "_id");

        $_SESSION[$objectID] = array(
            "foo" => array(
                "bar" => array(1, 2, 3)
            )
        );

        $attrReadMapping = new \ReflectionProperty(
            "ConfigFile",
            "_cfgUpdateReadMapping"
        );

        $attrReadMapping->setAccessible(true);
        $attrReadMapping->setValue(
            $this->object,
            array(
                "key" => "foo/bar"
            )
        );

        $expect = array(
            "key" => array(1, 2, 3)
        );

        $this->assertEquals(
            $expect,
            $this->object->getConfig()
        );
    }

    /**
     * Test for ConfigFile::getConfigArray
     *
     * @return void
     * @test
     */
    public function testGetConfigArray()
    {
        $objectID = $this->readAttribute($this->object, "_id");
        $reflection = new \ReflectionClass('ConfigFile');

        $_SESSION[$objectID] = array(
            "two" => array(1, 2, 3)
        );

        $attrPersistKeys = $reflection->getProperty('_persistKeys');
        $attrPersistKeys->setAccessible(true);
        $attrPersistKeys->setValue(
            $this->object,
            array(
                "one/foo" => array(),
                "two" => array(1, 2, 3),
                "three" => 3
            )
        );

        $attrCfg = $reflection->getProperty('_cfg');
        $attrCfg->setAccessible(true);
        $attrCfg->setValue(
            $this->object,
            array(
                "one" => array("foo" => "val"),
                "three" => "val2"
            )
        );

        $attrReadMapping = $reflection->getProperty("_cfgUpdateReadMapping");
        $attrReadMapping->setAccessible(true);
        $attrReadMapping->setValue(
            $this->object,
            array(
                "2" => "two",
                "3" => "foobar"
            )
        );

        $this->assertEquals(
            array(
                "one/foo" => "val",
                "three" => "val2",
                "2" => array(1, 2, 3)
            ),
            $this->object->getConfigArray()
        );


    }
}
?>
