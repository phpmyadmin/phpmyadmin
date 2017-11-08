<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Properties\Options\OptionsPropertyOneItem class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Properties\Options;

use PHPUnit\Framework\TestCase;

/**
 * Tests for PhpMyAdmin\Properties\Options\OptionsPropertyOneItem class
 *
 * @package PhpMyAdmin-test
 */
class OptionsPropertyOneItemTest extends TestCase
{
    protected $stub;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setup()
    {
        $this->stub = $this->getMockForAbstractClass('PhpMyAdmin\Properties\Options\OptionsPropertyOneItem');
    }

    /**
     * tearDown for test cases
     *
     * @return void
     */
    public function tearDown()
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
        $this->stub->setValues(array(1, 2));

        $this->assertEquals(
            array(1, 2),
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
