<?php
/**
 * tests for PhpMyAdmin\Properties\Options\OptionsPropertyItem class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Options;

use PhpMyAdmin\Properties\Options\OptionsPropertyItem;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * Tests for PhpMyAdmin\Properties\Options\OptionsPropertyItem class
 */
class OptionsPropertyItemTest extends AbstractTestCase
{
    protected $stub;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->stub = $this->getMockForAbstractClass(OptionsPropertyItem::class);
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
     * Test for
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyItem::getName
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyItem::setName
     *
     * @return void
     */
    public function testGetSetName()
    {
        $this->stub->setName('name123');

        $this->assertEquals(
            'name123',
            $this->stub->getName()
        );
    }

    /**
     * Test for
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyItem::getText
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyItem::setText
     *
     * @return void
     */
    public function testGetSetText()
    {
        $this->stub->setText('text123');

        $this->assertEquals(
            'text123',
            $this->stub->getText()
        );
    }

    /**
     * Test for
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyItem::getForce
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyItem::setForce
     *
     * @return void
     */
    public function testGetSetForce()
    {
        $this->stub->setForce('force123');

        $this->assertEquals(
            'force123',
            $this->stub->getForce()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\OptionsPropertyItem::getPropertyType
     *
     * @return void
     */
    public function testGetPropertyType()
    {
        $this->assertEquals(
            'options',
            $this->stub->getPropertyType()
        );
    }
}
