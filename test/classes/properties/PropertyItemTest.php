<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\properties\PropertyItem class
 *
 * @package PhpMyAdmin-test
 */

/**
 * Tests for PMA\libraries\properties\PropertyItem class
 *
 * @package PhpMyAdmin-test
 */
class PropertyItemTest extends PHPUnit_Framework_TestCase
{
    protected $stub;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setup()
    {
        $this->stub = $this->getMockForAbstractClass('PMA\libraries\properties\PropertyItem');
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
     * Test for PMA\libraries\properties\PropertyItem::getGroup
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
