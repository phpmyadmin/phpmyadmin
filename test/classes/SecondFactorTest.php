<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for SecondFactor class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\SecondFactor;

/**
 * Tests behaviour of SecondFactor class
 *
 * @package PhpMyAdmin-test
 */
class SecondFactorTest extends PmaTestCase
{
    public function setUp()
    {
        $GLOBALS['server'] = 1;
    }

    /**
     * Creates SecondFactor mock with custom configuration
     *
     * @param string $user   Username
     * @param array  $config Second factor authentication configuraiton
     *
     * @return SecondFactor
     */
    public function getSecondFactorMock($user, $config)
    {
        if (! isset($config['backend'])) {
            $config['backend'] = '';
        }
        if (! isset($config['settings'])) {
            $config['settings'] = [];
        }
        $result = $this->getMockbuilder('PhpMyAdmin\SecondFactor')
            ->setMethods(['readConfig'])
            ->disableOriginalConstructor()
            ->getMock();
        $result->method('readConfig')->willReturn($config);
        $result->__construct($user);
        return $result;
    }

    public function testNone()
    {
        $object = $this->getSecondFactorMock('user', ['type' => 'db']);
        $backend = $object->backend;
        $this->assertEquals('', $backend::$id);
        $this->assertTrue($object->check(true));
        $this->assertEquals('', $object->render());
        $this->assertTrue($object->configure(''));
        $this->assertEquals('', $object->setup());
    }

    public function testSimple()
    {
        $GLOBALS['cfg']['DBG']['simple2fa'] = true;
        $object = $this->getSecondFactorMock('user', ['type' => 'db', 'backend' => 'simple']);
        $backend = $object->backend;
        $this->assertEquals('simple', $backend::$id);
        $GLOBALS['cfg']['DBG']['simple2fa'] = false;
    }

    public function testLoad()
    {
        $object = new SecondFactor('user');
        $backend = $object->backend;
        $this->assertEquals('', $backend::$id);
    }

    public function testConfigureSimple()
    {
        $GLOBALS['cfg']['DBG']['simple2fa'] = true;
        $object = new SecondFactor('user');
        $this->assertTrue($object->configure('simple'));
        $backend = $object->backend;
        $this->assertEquals('simple', $backend::$id);
        $this->assertTrue($object->configure(''));
        $backend = $object->backend;
        $this->assertEquals('', $backend::$id);
        $GLOBALS['cfg']['DBG']['simple2fa'] = false;
        $object = new SecondFactor('user');
        $this->assertFalse($object->configure('simple'));
    }
}
