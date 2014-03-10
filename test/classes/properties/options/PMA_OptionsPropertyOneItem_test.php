<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for OptionsPropertyOneItem class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/properties/options/OptionsPropertyOneItem.class.php';

/**
 * Tests for OptionsPropertyOneItem class
 *
 * @package PhpMyAdmin-test
 */
class PMA_OptionsPropertyOneItem_Test extends PHPUnit_Framework_TestCase
{
    protected $stub;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setup()
    {
        $this->stub = $this->getMockForAbstractClass('OptionsPropertyOneItem');
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
     *     - OptionsPropertyOneItem::getValues
     *     - OptionsPropertyOneItem::setValues
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
     *     - OptionsPropertyOneItem::getLen
     *     - OptionsPropertyOneItem::setLen
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
     *     - OptionsPropertyOneItem::getForce
     *     - OptionsPropertyOneItem::setForce
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
     *     - OptionsPropertyOneItem::getDoc
     *     - OptionsPropertyOneItem::setDoc
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
     *     - OptionsPropertyOneItem::getSize
     *     - OptionsPropertyOneItem::setSize
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
?>
