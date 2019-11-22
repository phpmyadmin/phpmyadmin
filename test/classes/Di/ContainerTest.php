<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Di\Container class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Di;

use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Tests\PmaTestCase;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Tests for PhpMyAdmin\Di\Container class
 *
 * @package PhpMyAdmin-test
 */
class ContainerTest extends PmaTestCase
{
    /**
     * @access protected
     */
    protected $container;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $this->container = new Container();
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
        unset($this->container);
    }

    /**
     * Test for get
     *
     * @return void
     */
    public function testGetWithValidEntry()
    {
        $this->container->set('name', 'value');
        $this->assertSame('value', $this->container->get('name'));
    }

    /**
     * Test for get
     *
     * @return void
     */
    public function testGetThrowsNotFoundException()
    {
        try {
            $this->container->get('name');
            $this->assertTrue(false);
        } catch (NotFoundExceptionInterface $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test for has
     *
     * @return void
     */
    public function testHasReturnsTrueForValidEntry()
    {
        $this->container->set('name', 'value');
        $this->assertTrue($this->container->has('name'));
    }

    /**
     * Test for has
     *
     * @return void
     */
    public function testHasReturnsFalseForInvalidEntry()
    {
        $this->assertFalse($this->container->has('name'));
    }
}
