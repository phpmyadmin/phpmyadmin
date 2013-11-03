<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PropertyItem class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/properties/PropertyItem.class.php';

/**
 * Tests for PropertyItem class
 *
 * @package PhpMyAdmin-test
 */
class PMA_PropertyItem_Test extends PHPUnit_Framework_TestCase
{
    protected $stub;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setup()
    {
        $this->stub = $this->getMockForAbstractClass('PropertyItem');
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
     * Test for PropertyItem::getGroup
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
?>
