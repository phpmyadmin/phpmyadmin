<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for ExportPluginProperties class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/properties/plugins/ExportPluginProperties.class.php';
require_once 'libraries/properties/options/groups/OptionsPropertyRootGroup.class.php';
require_once 'test/classes/properties/plugins/PMA_ImportPluginProperties_test.php';

/**
 * Tests for ExportPluginProperties class. Extends PMA_ImportPluginProperties_Tests
 * and adds tests for methods that are not common to both
 *
 * @package PhpMyAdmin-test
 */
class PMA_ExportPluginProperties_Test extends PMA_ImportPluginProperties_Test
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
     * Test for ExportPluginProperties::getItemType
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
     *     - ExportPluginProperties::getForceFile
     *     - ExportPluginProperties::setForceFile
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
?>
