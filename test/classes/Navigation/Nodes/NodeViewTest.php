<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Navigation\Nodes\NodeView;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @covers \PhpMyAdmin\Navigation\Nodes\NodeView
 */
#[CoversClass(NodeView::class)]
class NodeViewTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
    }

    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = NodeFactory::getInstance('NodeView');
        self::assertIsArray($parent->links);
        self::assertSame([
            'text' => ['route' => '/sql', 'params' => ['pos' => 0, 'db' => null, 'table' => null]],
            'icon' => ['route' => '/table/structure', 'params' => ['db' => null, 'table' => null]],
        ], $parent->links);
        self::assertSame('b_props', $parent->icon['image']);
        self::assertSame('View', $parent->icon['title']);
        self::assertStringContainsString('view', $parent->classes);
    }
}
