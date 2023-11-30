<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\Nodes\NodeTableContainer;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeTableContainer::class)]
class NodeTableContainerTest extends AbstractTestCase
{
    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = new NodeTableContainer();
        $this->assertEquals(
            [
                'text' => ['route' => '/database/structure', 'params' => ['tbl_type' => 'table', 'db' => null]],
                'icon' => ['route' => '/database/structure', 'params' => ['tbl_type' => 'table', 'db' => null]],
            ],
            $parent->links,
        );
        $this->assertEquals('tables', $parent->realName);
        $this->assertStringContainsString('tableContainer', $parent->classes);
    }
}
