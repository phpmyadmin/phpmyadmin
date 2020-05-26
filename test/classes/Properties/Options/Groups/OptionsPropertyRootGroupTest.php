<?php
/**
 * tests for PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Options\Groups;

use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * tests for PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup class
 */
class OptionsPropertyRootGroupTest extends AbstractTestCase
{
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = new OptionsPropertyRootGroup();
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup::getItemType
     *
     * @return void
     */
    public function testGetItemType()
    {
        $this->assertEquals(
            'root',
            $this->object->getItemType()
        );
    }

    /**
     * Test for contable interface
     *
     * @return void
     */
    public function testCountable()
    {
        $this->assertCount(
            0,
            $this->object
        );
    }
}
