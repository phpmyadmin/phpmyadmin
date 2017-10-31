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

    public function testApplication()
    {
        $object = new SecondFactor('user');
        if (! in_array('application', $object->available)) {
            $this->markTestSkipped('google2fa not available');
        }
        /* Without providing code this should fail */
        $this->assertFalse($object->configure('application'));

        /* Invalid code */
        $_POST['2fa_code'] = 'invalid';
        $this->assertFalse($object->configure('application'));

        /* Generate valid code */
        $google2fa = $object->backend->google2fa;
        $_POST['2fa_code'] = $google2fa->oathHotp(
            $object->backend->config['secret'],
            $google2fa->getTimestamp()
        );
        $this->assertTrue($object->configure('application'));
    }

    public function testKey()
    {
        $object = new SecondFactor('user');
        if (! in_array('key', $object->available)) {
            $this->markTestSkipped('u2f-php-server not available');
        }
        /* Without providing code this should fail */
        $this->assertFalse($object->configure('key'));

        /* Invalid code */
        $_POST['u2f_registration_response'] = 'invalid';
        $this->assertFalse($object->configure('key'));
    }
}
