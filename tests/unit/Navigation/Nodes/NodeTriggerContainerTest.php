<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\Nodes\NodeTriggerContainer;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeTriggerContainer::class)]
class NodeTriggerContainerTest extends AbstractTestCase
{
    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = new NodeTriggerContainer(new Config());
        self::assertSame('/triggers', $parent->link->route);
        self::assertSame(['db' => null, 'table' => null], $parent->link->params);
        self::assertSame('/triggers', $parent->icon->route);
        self::assertSame(['db' => null, 'table' => null], $parent->icon->params);
        self::assertSame('triggers', $parent->realName);
    }
}
