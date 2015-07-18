<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for ImportPluginProperties class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/properties/plugins/ImportPluginProperties.class.php';
require_once 'libraries/properties/options/groups/OptionsPropertyRootGroup.class.php';
/**
 * tests for ImportPluginProperties class
 *
 * @package PhpMyAdmin-test
 */
class PMA_ImportPluginProperties_Test extends PHPUnit_Framework_TestCase
{
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setup()
    {
        $this->object = new ImportPluginProperties();
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
     * Test for ImportPluginProperties::getItemType
     *
     * @return void
     */
    public function testGetItemType()
    {
        $this->assertEquals(
            'import',
            $this->object->getItemType()
        );
    }

    /**
     * Test for
     *     - ImportPluginProperties::getOptionsText
     *     - ImportPluginProperties::setOptionsText
     *
     * @return void
     */
    public function testSetGetOptionsText()
    {
        $this->object->setOptionsText('options123');

        $this->assertEquals(
            'options123',
            $this->object->getOptionsText()
        );
    }

    /**
     * Test for
     *     - ImportPluginProperties::setMimeType
     *     - ImportPluginProperties::getMimeType
     *
     * @return void
     */
    public function testSetGetMimeType()
    {
        $this->object->setMimeType('mime123');

        $this->assertEquals(
            'mime123',
            $this->object->getMimeType()
        );
    }

}
