<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\Nodes\NodeIndex;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeIndex::class)]
class NodeIndexTest extends AbstractTestCase
{
    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = new NodeIndex(new Config(), 'default');
        self::assertSame('/table/indexes', $parent->link->route);
        self::assertSame(['db' => null, 'table' => null, 'index' => null], $parent->link->params);
        self::assertSame('/table/indexes', $parent->icon->route);
        self::assertSame(['db' => null, 'table' => null, 'index' => null], $parent->icon->params);
    }
}
