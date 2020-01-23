<?php
/**
 * tests for PhpMyAdmin\Properties\Plugins\ExportPluginProperties class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Plugins;

use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;

/**
 * Tests for PhpMyAdmin\Properties\Plugins\ExportPluginProperties class. Extends PMA_ImportPluginProperties_Tests
 * and adds tests for methods that are not common to both
 */
class ExportPluginPropertiesTest extends ImportPluginPropertiesTest
{
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        $this->object = new ExportPluginProperties();
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        unset($this->object);
    }

    /**
     * Test for PhpMyAdmin\Properties\Plugins\ExportPluginProperties::getItemType
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
     *     - PhpMyAdmin\Properties\Plugins\ExportPluginProperties::getForceFile
     *     - PhpMyAdmin\Properties\Plugins\ExportPluginProperties::setForceFile
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
