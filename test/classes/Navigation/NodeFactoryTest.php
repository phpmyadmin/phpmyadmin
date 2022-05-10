<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Navigation\Nodes\Node;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Navigation\NodeFactory
 */
class NodeFactoryTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
    }

    public function testDefaultNode(): void
    {
        $node = NodeFactory::getInstance();
        $this->assertEquals('default', $node->name);
        $this->assertEquals(Node::OBJECT, $node->type);
        $this->assertFalse($node->isGroup);
    }

    public function testDefaultContainer(): void
    {
        $node = NodeFactory::getInstance('Node', 'default', Node::CONTAINER);
        $this->assertEquals('default', $node->name);
        $this->assertEquals(Node::CONTAINER, $node->type);
        $this->assertFalse($node->isGroup);
    }

    public function testGroupContainer(): void
    {
        $node = NodeFactory::getInstance('Node', 'default', Node::CONTAINER, true);
        $this->assertEquals('default', $node->name);
        $this->assertEquals(Node::CONTAINER, $node->type);
        $this->assertTrue($node->isGroup);
    }

    /**
     * @group with-trigger-error
     */
    public function testFileError(): void
    {
        $this->expectError();
        $this->expectErrorMessage('Could not load class "PhpMyAdmin\Navigation\Nodes\Node"');
        NodeFactory::getInstance('NodeDoesNotExist');
    }

    /**
     * @group with-trigger-error
     */
    public function testClassNameError(): void
    {
        $this->expectError();
        $this->expectErrorMessage('Invalid class name "Node", using default of "Node"');
        NodeFactory::getInstance('Invalid');
    }
}
