<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\properties\options\OptionsPropertyOneItem class
 *
 * @package PhpMyAdmin-test
 */

/**
 * Tests for PMA\libraries\properties\options\OptionsPropertyOneItem class
 *
 * @package PhpMyAdmin-test
 */
class OptionsPropertyOneItemTest extends PHPUnit_Framework_TestCase
{
    protected $stub;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setup()
    {
        $this->stub = $this->getMockForAbstractClass('PMA\libraries\properties\options\OptionsPropertyOneItem');
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
     *     - PMA\libraries\properties\options\OptionsPropertyOneItem::getValues
     *     - PMA\libraries\properties\options\OptionsPropertyOneItem::setValues
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
     *     - PMA\libraries\properties\options\OptionsPropertyOneItem::getLen
     *     - PMA\libraries\properties\options\OptionsPropertyOneItem::setLen
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
     *     - PMA\libraries\properties\options\OptionsPropertyOneItem::getForce
     *     - PMA\libraries\properties\options\OptionsPropertyOneItem::setForce
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
     *     - PMA\libraries\properties\options\OptionsPropertyOneItem::getDoc
     *     - PMA\libraries\properties\options\OptionsPropertyOneItem::setDoc
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
     *     - PMA\libraries\properties\options\OptionsPropertyOneItem::getSize
     *     - PMA\libraries\properties\options\OptionsPropertyOneItem::setSize
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
