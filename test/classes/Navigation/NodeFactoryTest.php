<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Navigation\NodeType;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeFactory::class)]
class NodeFactoryTest extends AbstractTestCase
{
    public function testGetInstanceForNewNode(): void
    {
        $node = NodeFactory::getInstanceForNewNode('New', 'new_database italics');
        $this->assertEquals('New', $node->name);
        $this->assertEquals(NodeType::Object, $node->type);
        $this->assertFalse($node->isGroup);
        $this->assertEquals('New', $node->title);
        $this->assertTrue($node->isNew);
        $this->assertEquals('new_database italics', $node->classes);
    }
}
