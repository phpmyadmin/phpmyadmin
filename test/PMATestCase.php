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
     * @param mixed $param parameter for header method
     *
     * @return void
     */
    public function mockResponse($param)
    {
        $this->restoreInstance = PMA\libraries\Response::getInstance();

        $mockResponse = $this->getMockBuilder('PMA\libraries\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('header', 'headersSent', 'disable', 'isAjax'))
            ->getMock();

        $mockResponse->expects($this->any())
            ->method('headersSent')
            ->with()
            ->will($this->returnValue(false));

        $param = func_get_args();

        if (is_array($param[0])) {
            $header_method = $mockResponse->expects($this->exactly(count($param)))
                ->method('header');

            call_user_func_array(array($header_method, 'withConsecutive'), $param);
        } else {
            $mockResponse->expects($this->once())
                ->method('header')
                ->with($param[0]);
        }

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
