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
        self::assertSame(
            [
                'text' => ['route' => '/sql', 'params' => ['pos' => 0, 'db' => null, 'table' => null]],
                'icon' => ['route' => '/table/structure', 'params' => ['db' => null, 'table' => null]],
            ],
            $parent->links,
        );
        self::assertSame('b_props', $parent->icon['image']);
        self::assertSame('View', $parent->icon['title']);
        self::assertStringContainsString('view', $parent->classes);
    }
}
