<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for OptionsPropertyItem class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/properties/options/OptionsPropertyItem.class.php';

/**
 * Tests for OptionsPropertyItem class
 *
 * @package PhpMyAdmin-test
 */
class PMA_OptionsPropertyItem_Test extends PHPUnit_Framework_TestCase
{
    protected $stub;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setup()
    {
        $this->stub = $this->getMockForAbstractClass('OptionsPropertyItem');
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
     *     - OptionsPropertyItem::getName
     *     - OptionsPropertyItem::setName
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
     *     - OptionsPropertyItem::getText
     *     - OptionsPropertyItem::setText
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
     *     - OptionsPropertyItem::getForce
     *     - OptionsPropertyItem::setForce
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
     * Test for OptionsPropertyItem::getPropertyType
     *
     * @return void
     */
    public function testGetPropertyType()
    {
        if ((defined('HHVM_VERSION')
            && (version_compare(constant('HHVM_VERSION'), '3.8', 'lt')))
        ) {
            $this->markTestSkipped('Due to a bug in early versions of HHVM, this test cannot be completed.');
        }

        $this->assertEquals(
            'options',
            $this->stub->getPropertyType()
        );
    }
}
