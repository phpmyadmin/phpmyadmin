<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\Nodes\NodeEventContainer;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeEventContainer::class)]
class NodeEventContainerTest extends AbstractTestCase
{
    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = new NodeEventContainer($this->createDatabaseInterface(), new Config());
        self::assertSame('/events', $parent->link->route);
        self::assertSame(['db' => null], $parent->link->params);
        self::assertSame('/events', $parent->icon->route);
        self::assertSame(['db' => null], $parent->icon->params);
        self::assertSame('events', $parent->realName);
    }
}
