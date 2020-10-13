<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Plugins;

use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;

class ExportPluginPropertiesTest extends ImportPluginPropertiesTest
{
    /** @var ExportPluginProperties */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = new ExportPluginProperties();
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    public function testGetItemType(): void
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
     */
    public function testSetGetForceFile(): void
    {
        $this->object->setForceFile(true);

        $this->assertTrue(
            $this->object->getForceFile()
        );
    }
}
