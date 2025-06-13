<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\Nodes\NodeProcedureContainer;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeProcedureContainer::class)]
class NodeProcedureContainerTest extends AbstractTestCase
{
    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = new NodeProcedureContainer(new Config());
        self::assertSame('/database/routines', $parent->link->route);
        self::assertSame(['type' => 'PROCEDURE', 'db' => null], $parent->link->params);
        self::assertSame('procedures', $parent->realName);
        self::assertSame('/database/routines', $parent->icon->route);
        self::assertSame(['type' => 'PROCEDURE', 'db' => null], $parent->icon->params);
    }
}
