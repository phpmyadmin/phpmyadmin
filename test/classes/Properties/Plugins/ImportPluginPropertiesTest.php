<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Properties\Plugins\ImportPluginProperties class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Properties\Plugins;

use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use PHPUnit\Framework\TestCase;

/**
 * tests for PhpMyAdmin\Properties\Plugins\ImportPluginProperties class
 *
 * @package PhpMyAdmin-test
 */
class ImportPluginPropertiesTest extends TestCase
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
     * Test for PhpMyAdmin\Properties\Plugins\ImportPluginProperties::getItemType
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
     *     - PhpMyAdmin\Properties\Plugins\ImportPluginProperties::getOptionsText
     *     - PhpMyAdmin\Properties\Plugins\ImportPluginProperties::setOptionsText
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
     *     - PhpMyAdmin\Properties\Plugins\ImportPluginProperties::setMimeType
     *     - PhpMyAdmin\Properties\Plugins\ImportPluginProperties::getMimeType
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
