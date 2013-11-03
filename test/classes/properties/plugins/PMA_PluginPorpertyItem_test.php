<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PluginPropertyItem class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/properties/plugins/PluginPropertyItem.class.php';

/**
 * Tests for PluginPropertyItem class
 *
 * @package PhpMyAdmin-test
 */
class PMA_PluginPropertyItem_Test extends PHPUnit_Framework_TestCase
{
    protected $stub;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setup()
    {
        $this->stub = $this->getMockForAbstractClass('PluginPropertyItem');
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
     * Test for PluginPropertyItem::getPropertyType
     *
     * @return void
     */
    public function testGetPropertyType()
    {
        $this->assertEquals(
            "plugin",
            $this->stub->getPropertyType()
        );
    }
}
?>
