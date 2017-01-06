<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Base class for phpMyAdmin tests
 *
 * @package PhpMyAdmin-test
 */

/**
 * Base class for phpMyAdmin tests.
 *
 * @package PhpMyAdmin-test
 */
class PMATestCase extends PHPUnit_Framework_TestCase
{
    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass()
    {
        require 'libraries/config.default.php';
        $GLOBALS['cfg'] = $cfg;
    }
    /**
     * Creates mock of Response object
     *
     * @param string $param parameter for header method
     *
     * @return void
     */
    public function mockResponse($param)
    {
        $restoreInstance = PMA\libraries\Response::getInstance();

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

        $attrInstance = new ReflectionProperty('PMA\libraries\Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue($mockResponse);
        $attrInstance->setValue($restoreInstance);
    }
}