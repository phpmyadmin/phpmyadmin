<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Options;

use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\OptionsPropertyGroup;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/** @covers \PhpMyAdmin\Properties\Options\OptionsPropertyGroup */
class OptionsPropertyGroupTest extends AbstractTestCase
{
    /** @var OptionsPropertyGroup&MockObject */
    protected OptionsPropertyGroup $stub;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->stub = $this->getMockForAbstractClass(OptionsPropertyGroup::class);
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->stub);
    }

    public function testAddProperty(): void
    {
        $propertyItem = new BoolPropertyItem();
        $this->stub->addProperty($propertyItem);
        $this->stub->addProperty($propertyItem);

        $this->assertTrue(
            $this->stub->getProperties()->contains($propertyItem),
        );
        $this->assertEquals(
            1,
            $this->stub->getNrOfProperties(),
        );
    }

    public function testRemoveProperty(): void
    {
        $propertyItem = new BoolPropertyItem();

        $this->stub->addProperty($propertyItem);
        $this->assertTrue(
            $this->stub->getProperties()->contains($propertyItem),
        );

        $this->stub->removeProperty($propertyItem);
        $this->assertFalse(
            $this->stub->getProperties()->contains($propertyItem),
        );
    }

    public function testGetGroup(): void
    {
        $this->assertInstanceOf(
            OptionsPropertyGroup::class,
            $this->stub->getGroup(),
        );
    }

    public function testGetProperties(): void
    {
        $propertyItem = new BoolPropertyItem();
        $this->stub->addProperty($propertyItem);

        $this->assertTrue(
            $this->stub->getProperties()->contains($propertyItem),
        );
    }

    public function testGetNrOfProperties(): void
    {
        $propertyItem = new BoolPropertyItem();
        $this->stub->addProperty($propertyItem);
        $this->stub->addProperty($propertyItem);
        $propertyItem2 = new BoolPropertyItem();
        $this->stub->addProperty($propertyItem2);
        $this->assertEquals(
            2,
            $this->stub->getNrOfProperties(),
        );
    }
}
