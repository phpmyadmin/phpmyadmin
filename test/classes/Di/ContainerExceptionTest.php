<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Di\ContainerException class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Di;

use PhpMyAdmin\Di\ContainerException;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PhpMyAdmin\Di\ContainerException class
 *
 * @package PhpMyAdmin-test
 */
class ContainerExceptionTest extends PmaTestCase
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
