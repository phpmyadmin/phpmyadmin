<?php
/**
 * tests for PhpMyAdmin\Properties\Plugins\PluginPropertyItem class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Plugins;

use PHPUnit\Framework\TestCase;

/**
 * Tests for PhpMyAdmin\Properties\Plugins\PluginPropertyItem class
 */
class PluginPropertyItemTest extends TestCase
{
    protected $stub;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        $this->stub = $this->getMockForAbstractClass('PhpMyAdmin\Properties\Plugins\PluginPropertyItem');
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
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
            'plugin',
            $this->stub->getPropertyType()
        );
    }
}
