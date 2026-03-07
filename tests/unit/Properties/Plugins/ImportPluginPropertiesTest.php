<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Plugins;

use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ImportPluginProperties::class)]
class ImportPluginPropertiesTest extends AbstractTestCase
{
    protected ImportPluginProperties $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->object = new ImportPluginProperties();
    }

    /**
     * Test for
     *     - PhpMyAdmin\Properties\Plugins\ImportPluginProperties::setMimeType
     *     - PhpMyAdmin\Properties\Plugins\ImportPluginProperties::getMimeType
     */
    public function testSetGetMimeType(): void
    {
        $this->object->setMimeType('mime123');

        self::assertSame(
            'mime123',
            $this->object->getMimeType(),
        );
    }
}
