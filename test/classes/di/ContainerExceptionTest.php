<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PMA\libraries\di\ContainerException class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'test/PMATestCase.php';

use PMA\libraries\di\ContainerException;

/**
 * Tests for PMA\libraries\di\ContainerException class
 *
 * @package PhpMyAdmin-test
 */
class ContainerExceptionTest extends PMATestCase
{
    /**
     * @access protected
     */
    protected $exception;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $this->exception = new ContainerException();
    }

    /**
     * Tears down the fixture.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->exception);
    }

    /**
     * Test for ContainerException
     *
     * @return void
     */
    public function testContainerExceptionImplementsInteface()
    {
        $this->assertInstanceOf(
            'Psr\Container\ContainerExceptionInterface',
            $this->exception
        );
    }

    /**
     * Test for ContainerException
     *
     * @return void
     */
    public function testContainerExceptionExtendsException()
    {
        $this->assertInstanceOf(
            'Exception',
            $this->exception
        );
    }
}
