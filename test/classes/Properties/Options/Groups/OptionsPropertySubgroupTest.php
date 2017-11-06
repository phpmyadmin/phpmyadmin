<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Properties\Options\Groups\OptionsPropertySubgroup class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Properties\Options\Groups;

use PhpMyAdmin\Properties\Options\Groups\OptionsPropertySubgroup;
use PHPUnit\Framework\TestCase;

/**
 * tests for PhpMyAdmin\Properties\Options\Groups\OptionsPropertySubgroup class
 *
 * @package PhpMyAdmin-test
 */
class OptionsPropertySubgroupTest extends TestCase
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
     * Test for PhpMyAdmin\Properties\Options\Groups\OptionsPropertySubgroup::getItemType
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
     *     - PhpMyAdmin\Properties\Options\Groups\OptionsPropertySubgroup::getSubgroupHeader
     *     - PhpMyAdmin\Properties\Options\Groups\OptionsPropertySubgroup::setSubgroupHeader
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
