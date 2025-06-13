<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\Nodes\NodeFunctionContainer;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeFunctionContainer::class)]
class NodeFunctionContainerTest extends AbstractTestCase
{
    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = new NodeFunctionContainer(new Config());
        self::assertSame('/database/routines', $parent->link->route);
        self::assertSame(['type' => 'FUNCTION', 'db' => null], $parent->link->params);
        self::assertSame('/database/routines', $parent->icon->route);
        self::assertSame(['type' => 'FUNCTION', 'db' => null], $parent->icon->params);
        self::assertSame('functions', $parent->realName);
    }
}
