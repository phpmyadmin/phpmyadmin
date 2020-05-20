<?php
/**
 * tests for PhpMyAdmin\Properties\Options\OptionsPropertyOneItem class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Options;

use PhpMyAdmin\Properties\Options\OptionsPropertyOneItem;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PhpMyAdmin\Properties\Options\OptionsPropertyOneItem class
 */
class OptionsPropertyOneItemTest extends TestCase
{
    protected $stub;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        $this->stub = $this->getMockForAbstractClass(OptionsPropertyOneItem::class);
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        unset($this->stub);
    }

    /**
     * Test for
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::getValues
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::setValues
     *
     * @return void
     */
    public function testGetSetValues()
    {
        $this->stub->setValues([1, 2]);

        $this->assertEquals(
            [
                1,
                2,
            ],
            $this->stub->getValues()
        );
    }

    /**
     * Test for
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::getLen
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::setLen
     *
     * @return void
     */
    public function testGetSetLen()
    {
        $this->stub->setLen(12);

        $this->assertEquals(
            12,
            $this->stub->getLen()
        );
    }

    /**
     * Test for
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::getForce
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::setForce
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
     * Test for
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::getDoc
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::setDoc
     *
     * @return void
     */
    public function testGetSetDoc()
    {
        $this->stub->setDoc('doc123');

        $this->assertEquals(
            'doc123',
            $this->stub->getDoc()
        );
    }

    /**
     * Test for
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::getSize
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem::setSize
     *
     * @return void
     */
    public function testGetSetSize()
    {
        $this->stub->setSize(22);

        $this->assertEquals(
            22,
            $this->stub->getSize()
        );
    }
}
