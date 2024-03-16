<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Plugins;

use PhpMyAdmin\Properties\Plugins\PluginPropertyItem;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PluginPropertyItem::class)]
class PluginPropertyItemTest extends AbstractTestCase
{
    protected PluginPropertyItem $stub;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->stub = $this->getMockBuilder(PluginPropertyItem::class)
            ->onlyMethods(['getItemType'])
            ->getMock();
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->stub);
    }

    public function testGetPropertyType(): void
    {
        self::assertSame(
            'plugin',
            $this->stub->getPropertyType(),
        );
    }
}
