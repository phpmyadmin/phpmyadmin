<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\Nodes\NodeTrigger;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeTrigger::class)]
class NodeTriggerTest extends AbstractTestCase
{
    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = new NodeTrigger(new Config(), 'default');
        self::assertSame(
            [
                'text' => [
                    'route' => '/triggers',
                    'params' => ['edit_item' => 1, 'db' => null, 'item_name' => null],
                ],
            ],
            $parent->links,
        );
        self::assertSame('/triggers', $parent->icon->route);
        self::assertSame(
            ['export_item' => 1, 'db' => null, 'item_name' => null],
            $parent->icon->params,
        );
    }
}
