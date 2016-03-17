<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\properties\options\groups\OptionsPropertySubgroup class
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\properties\options\groups\OptionsPropertySubgroup;

/**
 * tests for PMA\libraries\properties\options\groups\OptionsPropertySubgroup class
 *
 * @package PhpMyAdmin-test
 */
class OptionsPropertySubgroupTest extends PHPUnit_Framework_TestCase
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
     * Test for PMA\libraries\properties\options\groups\OptionsPropertySubgroup::getItemType
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
     *     - PMA\libraries\properties\options\groups\OptionsPropertySubgroup::getSubgroupHeader
     *     - PMA\libraries\properties\options\groups\OptionsPropertySubgroup::setSubgroupHeader
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
