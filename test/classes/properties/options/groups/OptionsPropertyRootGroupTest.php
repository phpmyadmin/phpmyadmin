<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\properties\options\groups\OptionsPropertyRootGroup class
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\properties\options\groups\OptionsPropertyRootGroup;

/**
 * tests for PMA\libraries\properties\options\groups\OptionsPropertyRootGroup class
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
     * Test for PMA\libraries\properties\options\groups\OptionsPropertyRootGroup::getItemType
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
