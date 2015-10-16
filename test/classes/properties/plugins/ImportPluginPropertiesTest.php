<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\properties\plugins\ImportPluginProperties class
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\properties\plugins\ImportPluginProperties;

/**
 * tests for PMA\libraries\properties\plugins\ImportPluginProperties class
 *
 * @package PhpMyAdmin-test
 */
class ImportPluginPropertiesTest extends PHPUnit_Framework_TestCase
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
     * Test for PMA\libraries\properties\plugins\ImportPluginProperties::getItemType
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
     *     - PMA\libraries\properties\plugins\ImportPluginProperties::getOptionsText
     *     - PMA\libraries\properties\plugins\ImportPluginProperties::setOptionsText
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
     *     - PMA\libraries\properties\plugins\ImportPluginProperties::setMimeType
     *     - PMA\libraries\properties\plugins\ImportPluginProperties::getMimeType
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
