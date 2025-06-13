<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\Nodes\NodeIndexContainer;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeIndexContainer::class)]
class NodeIndexContainerTest extends AbstractTestCase
{
    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = new NodeIndexContainer(new Config());
        self::assertSame('/table/structure', $parent->link->route);
        self::assertSame(['db' => null, 'table' => null], $parent->link->params);
        self::assertSame('/table/structure', $parent->icon->route);
        self::assertSame(['db' => null, 'table' => null], $parent->icon->params);
        self::assertSame('indexes', $parent->realName);
    }
}
