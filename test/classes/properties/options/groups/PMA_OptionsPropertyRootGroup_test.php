<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for OptionsPropertyRootGroup class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/properties/options/groups/'
    . 'OptionsPropertyRootGroup.class.php';

/**
 * tests for OptionsPropertyRootGroup class
 *
 * @package PhpMyAdmin-test
 */
class PMA_OptionsPropertyRootGroup_Test extends PHPUnit_Framework_TestCase
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
     * Test for OptionsPropertyRootGroup::getItemType
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

}
