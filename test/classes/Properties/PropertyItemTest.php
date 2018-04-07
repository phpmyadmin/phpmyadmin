<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Properties\PropertyItem class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Properties;

use PHPUnit\Framework\TestCase;

/**
 * Tests for PhpMyAdmin\Properties\PropertyItem class
 *
 * @package PhpMyAdmin-test
 */
class PropertyItemTest extends TestCase
{
    protected $stub;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setup()
    {
        $this->stub = $this->getMockForAbstractClass('PhpMyAdmin\Properties\PropertyItem');
    }

    /**
     * tearDown for test cases
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->stub);
    }

    /**
     * Test for PhpMyAdmin\Properties\PropertyItem::getGroup
     *
     * @return void
     */
    public function testGetGroup()
    {
        $this->assertEquals(
            null,
            $this->stub->getGroup()
        );
    }
}
