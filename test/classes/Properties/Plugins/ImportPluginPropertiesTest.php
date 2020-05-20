<?php
/**
 * tests for PhpMyAdmin\Properties\Plugins\ImportPluginProperties class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Plugins;

use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * tests for PhpMyAdmin\Properties\Plugins\ImportPluginProperties class
 */
class ImportPluginPropertiesTest extends AbstractTestCase
{
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        $this->object = new ImportPluginProperties();
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
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
