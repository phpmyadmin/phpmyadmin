<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\Nodes\NodeView;
use PhpMyAdmin\Tests\AbstractTestCase;

/** @covers \PhpMyAdmin\Navigation\Nodes\NodeView */
class NodeViewTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $GLOBALS['server'] = 0;
    }

    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = new NodeView('default');
        $this->assertEquals(
            [
                'text' => ['route' => '/sql', 'params' => ['pos' => 0, 'db' => null, 'table' => null]],
                'icon' => ['route' => '/table/structure', 'params' => ['db' => null, 'table' => null]],
            ],
            $parent->links,
        );
        $this->assertEquals('b_props', $parent->icon['image']);
        $this->assertEquals('View', $parent->icon['title']);
        $this->assertStringContainsString('view', $parent->classes);
    }
}
