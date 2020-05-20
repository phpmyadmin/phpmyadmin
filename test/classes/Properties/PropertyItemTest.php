<?php
/**
 * tests for PhpMyAdmin\Properties\PropertyItem class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties;

use PhpMyAdmin\Properties\PropertyItem;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PhpMyAdmin\Properties\PropertyItem class
 */
class PropertyItemTest extends TestCase
{
    protected $stub;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        $this->stub = $this->getMockForAbstractClass(PropertyItem::class);
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
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
