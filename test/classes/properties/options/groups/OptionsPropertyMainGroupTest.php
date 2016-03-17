<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\properties\options\groups\OptionsPropertyMainGroup class
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\properties\options\groups\OptionsPropertyMainGroup;

/**
 * tests for PMA\libraries\properties\options\groups\OptionsPropertyMainGroup class
 *
 * @package PhpMyAdmin-test
 */
class OptionsPropertyMainGroupTest extends PHPUnit_Framework_TestCase
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
     * Test for PMA\libraries\properties\options\groups\OptionsPropertyMainGroup::getItemType
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
