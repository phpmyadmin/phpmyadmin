<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\properties\plugins\ExportPluginProperties class
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\properties\plugins\ExportPluginProperties;

require_once 'test/classes/properties/plugins/ImportPluginPropertiesTest.php';

/**
 * Tests for PMA\libraries\properties\plugins\ExportPluginProperties class. Extends PMA_ImportPluginProperties_Tests
 * and adds tests for methods that are not common to both
 *
 * @package PhpMyAdmin-test
 */
class ExportPluginPropertiesTest extends ImportPluginPropertiesTest
{
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setup()
    {
        $this->object = new ExportPluginProperties();
    }

    /**
     * tearDown for test cases
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for PMA\libraries\properties\plugins\ExportPluginProperties::getItemType
     *
     * @return void
     */
    public function testGetItemType()
    {
        $this->assertEquals(
            'export',
            $this->object->getItemType()
        );
    }

    /**
     * Test for
     *     - PMA\libraries\properties\plugins\ExportPluginProperties::getForceFile
     *     - PMA\libraries\properties\plugins\ExportPluginProperties::setForceFile
     *
     * @return void
     */
    public function testSetGetForceFile()
    {
        $this->object->setForceFile(true);

        $this->assertTrue(
            $this->object->getForceFile()
        );
    }

}
