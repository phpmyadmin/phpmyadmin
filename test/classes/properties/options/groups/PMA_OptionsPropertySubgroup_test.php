<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for OptionsPropertySubgroup class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/properties/options/groups/OptionsPropertySubgroup.class.php';
/**
 * tests for OptionsPropertySubgroup class
 *
 * @package PhpMyAdmin-test
 */
class PMA_OptionsPropertySubgroup_Test extends PHPUnit_Framework_TestCase
{
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setup()
    {
        $this->object = new OptionsPropertySubgroup();
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
     * Test for OptionsPropertySubgroup::getItemType
     *
     * @return void
     */
    public function testGetItemType()
    {
        $this->assertEquals(
            'subgroup',
            $this->object->getItemType()
        );
    }

    /**
     * Test for
     *     - OptionsPropertySubgroup::getSubgroupHeader
     *     - OptionsPropertySubgroup::setSubgroupHeader
     *
     * @return void
     */
    public function testGetSetSubgroupHeader()
    {
        $this->object->setSubgroupHeader('subGroupHeader123');

        $this->assertEquals(
            'subGroupHeader123',
            $this->object->getSubgroupHeader()
        );
    }
}
