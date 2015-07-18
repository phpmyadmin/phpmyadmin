<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for OptionsPropertyMainGroup class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/properties/options/groups/OptionsPropertyMainGroup.class.php';
/**
 * tests for OptionsPropertyMainGroup class
 *
 * @package PhpMyAdmin-test
 */
class PMA_OptionsPropertyMainGroup_Test extends PHPUnit_Framework_TestCase
{
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setup()
    {
        $this->object = new OptionsPropertyMainGroup();
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
     * Test for OptionsPropertyMainGroup::getItemType
     *
     * @return void
     */
    public function testGetItemType()
    {
        $this->assertEquals(
            'main',
            $this->object->getItemType()
        );
    }

}
