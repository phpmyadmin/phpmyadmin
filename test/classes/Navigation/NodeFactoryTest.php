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
        self::assertSame('default', $node->name);
        self::assertSame(Node::OBJECT, $node->type);
        self::assertFalse($node->isGroup);
    }

    public function testDefaultContainer(): void
    {
        $node = NodeFactory::getInstance('Node', 'default', Node::CONTAINER);
        self::assertSame('default', $node->name);
        self::assertSame(Node::CONTAINER, $node->type);
        self::assertFalse($node->isGroup);
    }

    public function testGroupContainer(): void
    {
        $node = NodeFactory::getInstance('Node', 'default', Node::CONTAINER, true);
        self::assertSame('default', $node->name);
        self::assertSame(Node::CONTAINER, $node->type);
        self::assertTrue($node->isGroup);
    }

    /**
     * @group with-trigger-error
     * @requires PHPUnit < 10
     */
    public function testFileError(): void
    {
        $this->expectError();
        $this->expectErrorMessage('Could not load class "PhpMyAdmin\Navigation\Nodes\Node"');
        NodeFactory::getInstance('NodeDoesNotExist');
    }

    /**
     * @group with-trigger-error
     * @requires PHPUnit < 10
     */
    public function testClassNameError(): void
    {
        $this->expectError();
        $this->expectErrorMessage('Invalid class name "Node", using default of "Node"');
        NodeFactory::getInstance('Invalid');
    }
}
