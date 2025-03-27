<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Options\Groups;

use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup
 */
class OptionsPropertyMainGroupTest extends AbstractTestCase
{
    /** @var OptionsPropertyMainGroup */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = new OptionsPropertyMainGroup();
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
        self::assertSame('main', $this->object->getItemType());
    }
}
