<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup class
 *
 * @package PhpMyAdmin-test
 */

use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;

/**
 * tests for PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup class
 *
 * @package PhpMyAdmin-test
 */
class OptionsPropertyRootGroupTest extends PHPUnit_Framework_TestCase
{
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setup()
    {
        $this->object = new OptionsPropertyRootGroup();
    }

    /**
     * tearDown for test cases
     *
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup::getItemType
     *
     * @return void
     */
    public function testGetItemType()
    {
        $this->assertEquals(
            'root',
            $this->object->getItemType()
        );
    }

    /**
     * Test for contable interface
     *
     * @return void
     */
    public function testCountable()
    {
        $this->assertEquals(
            0,
            count($this->object)
        );
    }

}
