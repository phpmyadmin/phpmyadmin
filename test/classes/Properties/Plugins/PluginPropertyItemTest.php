<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Properties\Plugins\PluginPropertyItem class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Properties\Plugins;

use PHPUnit\Framework\TestCase;

/**
 * Tests for PhpMyAdmin\Properties\Plugins\PluginPropertyItem class
 *
 * @package PhpMyAdmin-test
 */
class PluginPropertyItemTest extends TestCase
{
    protected $stub;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setup()
    {
        $this->stub = $this->getMockForAbstractClass('PhpMyAdmin\Properties\Plugins\PluginPropertyItem');
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
     * Test for PhpMyAdmin\Properties\Plugins\PluginPropertyItem::getPropertyType
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
