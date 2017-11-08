<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Base class for phpMyAdmin tests
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Response;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Base class for phpMyAdmin tests
 *
 * @package PhpMyAdmin-test
 */
class PmaTestCase extends TestCase
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
    public function mockResponse()
    {
        $this->restoreInstance = Response::getInstance();

        $mockResponse = $this->getMockBuilder('PhpMyAdmin\Response')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'header', 'headersSent', 'disable', 'isAjax',
                'setRequestStatus', 'addJSON', 'addHTML',
                'getFooter', 'getHeader','httpResponseCode',
            ))
            ->getMock();

        $mockResponse->expects($this->any())
            ->method('headersSent')
            ->with()
            ->will($this->returnValue(false));

        $param = func_get_args();

        if (count($param) > 0) {
            if (is_array($param[0])) {
                if (is_array($param[0][0]) && count($param) == 1) {
                    $param = $param[0];
                    if(is_int(end($param))){
                        $http_response_code_param = end($param);
                        $param = array_slice($param, 0, -1);

                        $header_method = $mockResponse->expects($this->once())
                        ->method('httpResponseCode')->with($http_response_code_param);
                    }
                }

                $header_method = $mockResponse->expects($this->exactly(count($param)))
                    ->method('header');

                call_user_func_array(array($header_method, 'withConsecutive'), $param);

            } else {
                $mockResponse->expects($this->once())
                    ->method('header')
                    ->with($param[0]);
            }
        }

        $this->attrInstance = new ReflectionProperty('PhpMyAdmin\Response', '_instance');
        $this->attrInstance->setAccessible(true);
        $this->attrInstance->setValue($mockResponse);

        return $mockResponse;
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
