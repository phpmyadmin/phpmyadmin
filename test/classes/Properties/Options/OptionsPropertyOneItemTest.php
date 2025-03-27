<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Options;

use PhpMyAdmin\Properties\Options\OptionsPropertyOneItem;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \PhpMyAdmin\Properties\Options\OptionsPropertyOneItem
 */
class OptionsPropertyOneItemTest extends AbstractTestCase
{
    /** @var OptionsPropertyOneItem|MockObject  */
    protected $stub;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->stub = $this->getMockForAbstractClass(OptionsPropertyOneItem::class);
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
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::getValues
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::setValues
     */
    public function testGetSetValues(): void
    {
        $this->stub->setValues([1, 2]);

        self::assertEquals([
            1,
            2,
        ], $this->stub->getValues());
    }

    /**
     * Test for
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::getLen
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::setLen
     */
    public function testGetSetLen(): void
    {
        $this->stub->setLen(12);

        self::assertEquals(12, $this->stub->getLen());
    }

    /**
     * Test for
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::getForce
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::setForce
     */
    public function testGetSetForce(): void
    {
        $this->stub->setForce('force123');

        self::assertEquals('force123', $this->stub->getForce());
    }

    /**
     * Test for
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::getDoc
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::setDoc
     */
    public function testGetSetDoc(): void
    {
        $this->stub->setDoc('doc123');

        self::assertEquals('doc123', $this->stub->getDoc());
    }

    /**
     * Test for
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::getSize
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::setSize
     */
    public function testGetSetSize(): void
    {
        $this->stub->setSize(22);

        self::assertEquals(22, $this->stub->getSize());
    }
}
