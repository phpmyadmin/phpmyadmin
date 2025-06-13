<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\Nodes\NodeView;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeView::class)]
class NodeViewTest extends AbstractTestCase
{
    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = new NodeView(new Config(), 'default');
        self::assertSame('/sql', $parent->link->route);
        self::assertSame(['pos' => 0, 'db' => null, 'table' => null], $parent->link->params);
        self::assertSame('b_props', $parent->icon->image);
        self::assertSame('View', $parent->icon->title);
        self::assertSame('/table/structure', $parent->icon->route);
        self::assertSame(['db' => null, 'table' => null], $parent->icon->params);
        self::assertStringContainsString('view', $parent->classes);
    }
}
