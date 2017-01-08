<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Base class for phpMyAdmin tests
 *
 * @package PhpMyAdmin-test
 */

class PMATestCase extends PHPUnit_Framework_TestCase
{
    protected $restoreInstance = null;
    protected $attrInstance = null;

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass()
    {
        require 'libraries/config.default.php';
        $GLOBALS['cfg'] = $cfg;
    }

    /**
     * Creates mock of Response object for header testing
     *
     * @param string $param parameter for header method
     *
     * @return void
     */
    public function mockResponse($param)
    {
        $this->restoreInstance = PMA\libraries\Response::getInstance();

        $mockResponse = $this->getMockBuilder('PMA\libraries\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('header', 'headersSent'))
            ->getMock();

        $mockResponse->expects($this->once())
            ->method('header')
            ->with($param);

        $mockResponse->expects($this->any())
            ->method('headersSent')
            ->with()
            ->will($this->returnValue(false));

        $this->attrInstance = new ReflectionProperty('PMA\libraries\Response', '_instance');
        $this->attrInstance->setAccessible(true);
        $this->attrInstance->setValue($mockResponse);
    }

    /**
     *Tear down function for mockResponse method
     *
     *@return void
     */
    protected function tearDown()
    {
        if (! is_null($this->attrInstance) && ! is_null($this->restoreInstance)) {
            $this->attrInstance->setValue($this->restoreInstance);
            $this->restoreInstance = null;
            $this->attrInstance = null;
        }
    }
}
