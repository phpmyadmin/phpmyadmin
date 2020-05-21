<?php
/**
 * tests for PhpMyAdmin\Properties\PropertyItem class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties;

use PhpMyAdmin\Properties\PropertyItem;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * Tests for PhpMyAdmin\Properties\PropertyItem class
 */
class PropertyItemTest extends AbstractTestCase
{
    protected $stub;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->stub = $this->getMockForAbstractClass(PropertyItem::class);
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
