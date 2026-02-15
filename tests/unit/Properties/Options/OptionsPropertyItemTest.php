<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Options;

use PhpMyAdmin\Properties\Options\OptionsPropertyItem;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(OptionsPropertyItem::class)]
class OptionsPropertyItemTest extends AbstractTestCase
{
    protected OptionsPropertyItem&MockObject $stub;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->stub = $this->getMockBuilder(OptionsPropertyItem::class)->onlyMethods([])->getMock();
    }

    /**
     * Test for
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyItem::getName
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyItem::setName
     */
    public function testGetSetName(): void
    {
        $this->stub->setName('name123');

        self::assertSame(
            'name123',
            $this->stub->getName(),
        );
    }

    /**
     * Test for
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyItem::getText
     *     - PhpMyAdmin\Properties\Options\OptionsPropertyItem::setText
     */
    public function testGetSetText(): void
    {
        $this->stub->setText('text123');

        self::assertSame(
            'text123',
            $this->stub->getText(),
        );
    }
}
