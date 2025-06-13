<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
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
        $parent = new NodeTableContainer(new Config());
        self::assertSame('/database/structure', $parent->link->route);
        self::assertSame(['tbl_type' => 'table', 'db' => null], $parent->link->params);
        self::assertSame('/database/structure', $parent->icon->route);
        self::assertSame(['tbl_type' => 'table', 'db' => null], $parent->icon->params);
        self::assertSame('tables', $parent->realName);
        self::assertStringContainsString('tableContainer', $parent->classes);
    }
}
