<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Options;

use PhpMyAdmin\Properties\Options\OptionsPropertyGroup;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;

class OptionsPropertyGroupTest extends AbstractTestCase
{
    /** @var OptionsPropertyGroup|MockObject */
    protected $stub;

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
        $properties = new ReflectionProperty(OptionsPropertyGroup::class, 'properties');
        $properties->setAccessible(true);

        $properties->setValue($this->stub, [1, 2, 3]);

        $this->stub->addProperty(2);
        $this->stub->addProperty('2');

        $this->assertEquals(
            [
                1,
                2,
                3,
                '2',
            ],
            $properties->getValue($this->stub)
        );
    }

    public function testRemoveProperty(): void
    {
        $properties = new ReflectionProperty(OptionsPropertyGroup::class, 'properties');
        $properties->setAccessible(true);

        $properties->setValue($this->stub, [1, 2, 'test', 3]);
        $this->stub->removeProperty('test');

        $this->assertEquals(
            [
                0 => 1,
                1 => 2,
                3 => 3,
            ],
            $properties->getValue($this->stub)
        );
    }

    public function testGetGroup(): void
    {
        $this->assertInstanceOf(
            OptionsPropertyGroup::class,
            $this->stub->getGroup()
        );
    }

    public function testGetProperties(): void
    {
        $properties = new ReflectionProperty(OptionsPropertyGroup::class, 'properties');
        $properties->setAccessible(true);
        $properties->setValue($this->stub, [1, 2, 3]);

        $this->assertEquals(
            [
                1,
                2,
                3,
            ],
            $this->stub->getProperties()
        );
    }

    public function testGetNrOfProperties(): void
    {
        $properties = new ReflectionProperty(OptionsPropertyGroup::class, 'properties');
        $properties->setAccessible(true);
        $properties->setValue($this->stub, [1, 2, 3]);

        $this->assertEquals(
            3,
            $this->stub->getNrOfProperties()
        );
    }
}
