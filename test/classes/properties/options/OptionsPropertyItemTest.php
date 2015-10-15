<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\properties\options\OptionsPropertyItem class
 *
 * @package PhpMyAdmin-test
 */

/**
 * Tests for PMA\libraries\properties\options\OptionsPropertyItem class
 *
 * @package PhpMyAdmin-test
 */
class OptionsPropertyItemTest extends PHPUnit_Framework_TestCase
{
    protected $stub;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setup()
    {
        $this->stub = $this->getMockForAbstractClass('PMA\libraries\properties\options\OptionsPropertyItem');
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
     *     - PMA\libraries\properties\options\OptionsPropertyItem::getName
     *     - PMA\libraries\properties\options\OptionsPropertyItem::setName
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
     *     - PMA\libraries\properties\options\OptionsPropertyItem::getText
     *     - PMA\libraries\properties\options\OptionsPropertyItem::setText
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
     *     - PMA\libraries\properties\options\OptionsPropertyItem::getForce
     *     - PMA\libraries\properties\options\OptionsPropertyItem::setForce
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
     * Test for PMA\libraries\properties\options\OptionsPropertyItem::getPropertyType
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
