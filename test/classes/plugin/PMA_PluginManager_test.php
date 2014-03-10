<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PluginManager class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/plugins/PluginManager.class.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/config.default.php';

/**
 * Dummy testObserver
 *
 * @package PhpMyAdmin-test
 */
class PMA_TestObserver implements SplObserver
{
    /**
     * udpate
     *
     * @param SplSubject $subject subject for observer
     *
     * @return null
     */
    public function update(SplSubject $subject)
    {
        return null;
    }

}
/**
 * tests for PluginManager class
 *
 * @package PhpMyAdmin-test
 */
class PMA_PluginManager_Test extends PHPUnit_Framework_TestCase
{
    protected $object;

    protected $attrStorage;
    /**
     * Configures global environment.
     *
     * @return void
     */
    function setup()
    {
        $GLOBALS['PMA_Config'] = new PMA_Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['server'] = 0;

        $this->object = new PluginManager(null);

        $this->attrStorage = new \ReflectionProperty('PluginManager', '_storage');
        $this->attrStorage->setAccessible(true);
    }

    /**
     * tearDown for test cases
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for PluginManager::__construct
     *
     * @return void
     */
    public function testConstructor()
    {
        $this->assertInstanceOf(
            'SplObjectStorage',
            $this->attrStorage->getValue($this->object)
        );
    }

    /**
     * Test for PluginManager::attach
     *
     * @return void
     */
    public function testAttach()
    {
        $observer = new PMA_TestObserver();

        $mock = $this->getMockBuilder('SplObjectStorage')
            ->disableOriginalConstructor()
            ->setMethods(array('attach'))
            ->getMock();

        $mock->expects($this->once())
            ->method('attach')
            ->with($observer);

        $this->attrStorage->setValue($this->object, $mock);

        $this->object->attach($observer);

    }

    /**
     * Test for PluginManager::detach
     *
     * @return void
     */
    public function testDetach()
    {
        $observer = new PMA_TestObserver();

        $mock = $this->getMockBuilder('SplObjectStorage')
            ->disableOriginalConstructor()
            ->setMethods(array('detach'))
            ->getMock();

        $mock->expects($this->once())
            ->method('detach')
            ->with($observer);

        $this->attrStorage->setValue($this->object, $mock);

        $this->object->detach($observer);
    }

    /**
     * Test for
     *     - PluginManager::getStorage
     *     - PluginManager::setStorage
     *
     * @return void
     */
    public function testSetGetStorage()
    {
        $s = new SplObjectStorage();
        $o1 = new StdClass;
        $s[$o1] = 'testData';

        $this->object->setStorage($s);

        $this->assertEquals(
            $s,
            $this->object->getStorage()
        );
    }

    /**
     * Test for
     *     - PluginManager::getStatus
     *     - PluginManager::setStatus
     *
     * @return void
     */
    public function testSetGetStatus()
    {
        $this->object->setStatus('active');

        $this->assertEquals(
            'active',
            $this->object->getStatus()
        );
    }
}
?>
