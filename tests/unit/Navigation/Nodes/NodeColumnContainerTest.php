<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\Nodes\NodeColumnContainer;
use PhpMyAdmin\Navigation\NodeType;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeColumnContainer::class)]
final class NodeColumnContainerTest extends AbstractTestCase
{
    public function testColumnContainer(): void
    {
        $nodeColumnContainer = new NodeColumnContainer(new Config());
        self::assertSame('Columns', $nodeColumnContainer->name);
        self::assertSame(NodeType::Container, $nodeColumnContainer->type);
        self::assertFalse($nodeColumnContainer->isGroup);
        self::assertSame('pause', $nodeColumnContainer->icon->image);
        self::assertSame('Columns', $nodeColumnContainer->icon->title);
        self::assertSame('/table/structure', $nodeColumnContainer->icon->route);
        self::assertSame(['db' => null, 'table' => null], $nodeColumnContainer->icon->params);
        self::assertSame('/table/structure', $nodeColumnContainer->link->route);
        self::assertSame(['db' => null, 'table' => null], $nodeColumnContainer->link->params);
        self::assertSame('columns', $nodeColumnContainer->realName);
        self::assertCount(1, $nodeColumnContainer->children);
        self::assertArrayHasKey(0, $nodeColumnContainer->children);
        $newNode = $nodeColumnContainer->children[0];
        self::assertSame('New', $newNode->name);
        self::assertSame('New', $newNode->link->title);
        self::assertTrue($newNode->isNew);
        self::assertSame('new_column italics', $newNode->classes);
        self::assertSame('b_column_add', $newNode->icon->image);
        self::assertSame('/table/add-field', $newNode->icon->route);
        self::assertSame('New', $newNode->icon->title);
        self::assertSame(
            ['field_where' => 'last', 'after_field' => '', 'db' => null, 'table' => null],
            $newNode->icon->params,
        );
        self::assertSame('/table/add-field', $newNode->link->route);
        self::assertSame(
            ['field_where' => 'last', 'after_field' => '', 'db' => null, 'table' => null],
            $newNode->link->params,
        );
    }
}
