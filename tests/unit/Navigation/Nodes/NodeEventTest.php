<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\Nodes\NodeEvent;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeEvent::class)]
class NodeEventTest extends AbstractTestCase
{
    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = new NodeEvent(new Config(), 'default');
        self::assertSame('/database/events', $parent->link->route);
        self::assertSame(['edit_item' => 1, 'db' => null, 'item_name' => null], $parent->link->params);
        self::assertSame('/database/events', $parent->icon->route);
        self::assertSame(['edit_item' => 1, 'db' => null, 'item_name' => null], $parent->icon->params);
    }
}
