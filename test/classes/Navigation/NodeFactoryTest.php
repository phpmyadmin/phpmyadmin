<?php
/**
 * Tests for NodeFactory class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Navigation\Nodes\Node;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Exception;

/**
 * Tests for NodeFactory class
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

    /**
     * Test for PhpMyAdmin\Navigation\NodeFactory::getInstance
     */
    public function testDefaultNode(): void
    {
        $node = NodeFactory::getInstance();
        $this->assertEquals('default', $node->name);
        $this->assertEquals(Node::OBJECT, $node->type);
        $this->assertFalse($node->isGroup);
    }

    /**
     * Test for PhpMyAdmin\Navigation\NodeFactory::getInstance
     */
    public function testDefaultContainer(): void
    {
        $node = NodeFactory::getInstance(
            'Node',
            'default',
            Node::CONTAINER
        );
        $this->assertEquals('default', $node->name);
        $this->assertEquals(Node::CONTAINER, $node->type);
        $this->assertFalse($node->isGroup);
    }

    /**
     * Test for PhpMyAdmin\Navigation\NodeFactory::getInstance
     */
    public function testGroupContainer(): void
    {
        $node = NodeFactory::getInstance(
            'Node',
            'default',
            Node::CONTAINER,
            true
        );
        $this->assertEquals('default', $node->name);
        $this->assertEquals(Node::CONTAINER, $node->type);
        $this->assertTrue($node->isGroup);
    }

    /**
     * Test for PhpMyAdmin\Navigation\NodeFactory::getInstance
     */
    public function testFileError(): void
    {
        $this->expectException(Exception::class);
        NodeFactory::getInstance('NodeDoesNotExist');
    }

    /**
     * Test for PhpMyAdmin\Navigation\NodeFactory::getInstance
     */
    public function testClassNameError(): void
    {
        $this->expectException(Exception::class);
        NodeFactory::getInstance('Invalid');
    }
}
