<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties;

use PhpMyAdmin\Properties\PropertyItem;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \PhpMyAdmin\Properties\PropertyItem
 */
#[CoversClass(PropertyItem::class)]
#[AllowMockObjectsWithoutExpectations]
class PropertyItemTest extends AbstractTestCase
{
    /** @var PropertyItem|MockObject */
    protected $stub;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->stub = $this->getMockBuilder(PropertyItem::class)
            ->onlyMethods(['getPropertyType', 'getItemType'])
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

    public function testGetGroup(): void
    {
        self::assertNull($this->stub->getGroup());
    }
}
