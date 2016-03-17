<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\properties\plugins\PluginPropertyItem class
 *
 * @package PhpMyAdmin-test
 */

/**
 * Tests for PMA\libraries\properties\plugins\PluginPropertyItem class
 *
 * @package PhpMyAdmin-test
 */
class PluginPropertyItemTest extends PHPUnit_Framework_TestCase
{
    protected $stub;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setup()
    {
        $this->stub = $this->getMockForAbstractClass('PMA\libraries\properties\plugins\PluginPropertyItem');
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
     * Test for PMA\libraries\properties\plugins\PluginPropertyItem::getPropertyType
     *
     * @return void
     */
    public function testGetPropertyType()
    {
        if ((defined('HHVM_VERSION')
            && (version_compare(constant('HHVM_VERSION'), '3.8', 'lt')))
        ) {
            $this->markTestSkipped('Due to a bug in early versions of HHVM, this test cannot be completed.');
        }

        $this->assertEquals(
            "plugin",
            $this->stub->getPropertyType()
        );
    }
}
