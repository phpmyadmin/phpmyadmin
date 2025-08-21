<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Options;

use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\OptionsPropertyGroup;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(OptionsPropertyGroup::class)]
class OptionsPropertyGroupTest extends AbstractTestCase
{
    protected OptionsPropertyGroup&MockObject $stub;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->stub = $this->getMockBuilder(OptionsPropertyGroup::class)
            ->onlyMethods(['getItemType'])
            ->getMock();
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

        self::assertTrue($this->stub->getProperties()->offsetExists($propertyItem));
        self::assertSame(1, $this->stub->getNrOfProperties());
    }

    public function testRemoveProperty(): void
    {
        $propertyItem = new BoolPropertyItem();

        $this->stub->addProperty($propertyItem);
        self::assertTrue($this->stub->getProperties()->offsetExists($propertyItem));

        $this->stub->removeProperty($propertyItem);
        self::assertFalse($this->stub->getProperties()->offsetExists($propertyItem));
    }

    public function testGetProperties(): void
    {
        $propertyItem = new BoolPropertyItem();
        $this->stub->addProperty($propertyItem);

        self::assertTrue($this->stub->getProperties()->offsetExists($propertyItem));
    }

    public function testGetNrOfProperties(): void
    {
        $propertyItem = new BoolPropertyItem();
        $this->stub->addProperty($propertyItem);
        $this->stub->addProperty($propertyItem);
        $propertyItem2 = new BoolPropertyItem();
        $this->stub->addProperty($propertyItem2);
        self::assertSame(2, $this->stub->getNrOfProperties());
    }
}
