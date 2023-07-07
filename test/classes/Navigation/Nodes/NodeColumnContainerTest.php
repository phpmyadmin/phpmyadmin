<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\Nodes\Node;
use PhpMyAdmin\Navigation\Nodes\NodeColumnContainer;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeColumnContainer::class)]
final class NodeColumnContainerTest extends AbstractTestCase
{
    public function testColumnContainer(): void
    {
        $nodeColumnContainer = new NodeColumnContainer();
        $this->assertSame('Columns', $nodeColumnContainer->name);
        $this->assertSame(Node::CONTAINER, $nodeColumnContainer->type);
        $this->assertFalse($nodeColumnContainer->isGroup);
        $this->assertSame(['image' => 'pause', 'title' => 'Columns'], $nodeColumnContainer->icon);
        $this->assertSame(
            [
                'text' => ['route' => '/table/structure', 'params' => ['db' => null, 'table' => null]],
                'icon' => ['route' => '/table/structure', 'params' => ['db' => null, 'table' => null]],
            ],
            $nodeColumnContainer->links,
        );
        $this->assertSame('columns', $nodeColumnContainer->realName);
        $this->assertCount(1, $nodeColumnContainer->children);
        $this->assertArrayHasKey(0, $nodeColumnContainer->children);
        $newNode = $nodeColumnContainer->children[0];
        $this->assertSame('New', $newNode->name);
        $this->assertSame('New', $newNode->title);
        $this->assertTrue($newNode->isNew);
        $this->assertSame('new_column italics', $newNode->classes);
        $this->assertSame(['image' => 'b_column_add', 'title' => 'New'], $newNode->icon);
        $this->assertSame(
            [
                'text' => [
                    'route' => '/table/add-field',
                    'params' => ['field_where' => 'last', 'after_field' => '', 'db' => null, 'table' => null],
                ],
                'icon' => [
                    'route' => '/table/add-field',
                    'params' => ['field_where' => 'last', 'after_field' => '', 'db' => null, 'table' => null],
                ],
            ],
            $newNode->links,
        );
    }
}
