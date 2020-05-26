<?php
/**
 * tests for PhpMyAdmin\Properties\Plugins\PluginPropertyItem class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Plugins;

use PhpMyAdmin\Properties\Plugins\PluginPropertyItem;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * Tests for PhpMyAdmin\Properties\Plugins\PluginPropertyItem class
 */
class PluginPropertyItemTest extends AbstractTestCase
{
    protected $stub;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->stub = $this->getMockForAbstractClass(PluginPropertyItem::class);
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();
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
