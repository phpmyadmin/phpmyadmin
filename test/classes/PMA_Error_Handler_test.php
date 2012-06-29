<?php
/**
 * Tests for displaing results
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/Error_Handler.class.php';

class PMA_Error_Handler_test extends PHPUnit_Framework_TestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $this->object = $this->getMockForAbstractClass('PMA_Error_Handler');

    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * Call protected functions by making the visibitlity to public.
     *
     * @param string $name   method name
     * @param array  $params parameters for the invocation
     *
     * @return the output from the protected method.
     */
    private function _callProtectedFunction($name, $params)
    {
        $class = new ReflectionClass('PMA_Error_Handler');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->object, $params);
    }

    /**
     * @param integer $errno   error number
     * @param string  $errstr  error string
     * @param string  $errfile error file
     * @param integer $errline error line
     * @param $output output from the handleError method
     *
     * @dataProvider providerForTestHandleError
     */
    public function testHandleError($errno, $errstr, $errfile, $errline, $output){
        $this->aseertEquals($this->handleError($errno, $errstr, $errfile, $errline),$output);
    }

    /**
     * @return array data for testHandleError
     */
    public function providerForTestHandleError()
    {
        return array(
            array(
                '1024',
                'Compile Error',
                'error.txt',
                12,
                ':)'
            )
        );
    }

}
