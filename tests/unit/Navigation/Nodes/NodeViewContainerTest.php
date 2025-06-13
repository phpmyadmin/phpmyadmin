<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\Nodes\NodeViewContainer;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeViewContainer::class)]
class NodeViewContainerTest extends AbstractTestCase
{
    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = new NodeViewContainer(new Config());
        self::assertSame('/database/structure', $parent->link->route);
        self::assertSame(['tbl_type' => 'view', 'db' => null], $parent->link->params);
        self::assertSame('/database/structure', $parent->icon->route);
        self::assertSame(['tbl_type' => 'view', 'db' => null], $parent->icon->params);
        self::assertSame('views', $parent->realName);
        self::assertStringContainsString('viewContainer', $parent->classes);
    }
}
