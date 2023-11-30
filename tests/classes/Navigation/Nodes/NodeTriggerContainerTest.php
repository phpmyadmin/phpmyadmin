<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\Nodes\NodeTriggerContainer;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeTriggerContainer::class)]
class NodeTriggerContainerTest extends AbstractTestCase
{
    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = new NodeTriggerContainer();
        $this->assertEquals(
            [
                'text' => ['route' => '/triggers', 'params' => ['db' => null, 'table' => null]],
                'icon' => ['route' => '/triggers', 'params' => ['db' => null, 'table' => null]],
            ],
            $parent->links,
        );
        $this->assertEquals('triggers', $parent->realName);
    }
}
