<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Navigation\Nodes\Node;
use PhpMyAdmin\Navigation\Nodes\NodeDatabase;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeFactory::class)]
class NodeFactoryTest extends AbstractTestCase
{
    public function testDefaultNode(): void
    {
        $node = NodeFactory::getInstance(Node::class);
        $this->assertEquals('default', $node->name);
        $this->assertEquals(Node::OBJECT, $node->type);
        $this->assertFalse($node->isGroup);
    }

    public function testDefaultContainer(): void
    {
        $node = NodeFactory::getInstance(Node::class, 'default', Node::CONTAINER);
        $this->assertEquals('default', $node->name);
        $this->assertEquals(Node::CONTAINER, $node->type);
        $this->assertFalse($node->isGroup);
    }

    public function testGroupContainer(): void
    {
        $node = NodeFactory::getInstance(Node::class, 'default', Node::CONTAINER, true);
        $this->assertEquals('default', $node->name);
        $this->assertEquals(Node::CONTAINER, $node->type);
        $this->assertTrue($node->isGroup);
    }

    public function testDatabaseNode(): void
    {
        $node = NodeFactory::getInstance(NodeDatabase::class, 'database_name');
        /** @phpstan-ignore-next-line */
        $this->assertInstanceOf(NodeDatabase::class, $node);
        $this->assertEquals('database_name', $node->name);
        $this->assertEquals(Node::OBJECT, $node->type);
        $this->assertFalse($node->isGroup);
    }

    public function testGetInstanceForNewNode(): void
    {
        $node = NodeFactory::getInstanceForNewNode('New', 'new_database italics');
        $this->assertEquals('New', $node->name);
        $this->assertEquals(Node::OBJECT, $node->type);
        $this->assertFalse($node->isGroup);
        $this->assertEquals('New', $node->title);
        $this->assertTrue($node->isNew);
        $this->assertEquals('new_database italics', $node->classes);
    }
}
