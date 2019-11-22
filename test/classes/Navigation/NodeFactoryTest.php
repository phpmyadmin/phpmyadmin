<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for NodeFactory class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Navigation;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Navigation\Nodes\Node;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for NodeFactory class
 *
 * @package PhpMyAdmin-test
 */
class NodeFactoryTest extends PmaTestCase
{
    /**
     * SetUp for test cases
     *
     * @return void
     */
    public function setUp()
    {
        $GLOBALS['server'] = 0;
    }

    /**
     * Test for PhpMyAdmin\Navigation\NodeFactory::getInstance
     *
     * @return void
     */
    public function testDefaultNode()
    {
        $node = NodeFactory::getInstance();
        $this->assertEquals('default', $node->name);
        $this->assertEquals(Node::OBJECT, $node->type);
        $this->assertEquals(false, $node->is_group);
    }

    /**
     * Test for PhpMyAdmin\Navigation\NodeFactory::getInstance
     *
     * @return void
     */
    public function testDefaultContainer()
    {
        $node = NodeFactory::getInstance(
            'Node',
            'default',
            Node::CONTAINER
        );
        $this->assertEquals('default', $node->name);
        $this->assertEquals(Node::CONTAINER, $node->type);
        $this->assertEquals(false, $node->is_group);
    }

    /**
     * Test for PhpMyAdmin\Navigation\NodeFactory::getInstance
     *
     * @return void
     */
    public function testGroupContainer()
    {
        $node = NodeFactory::getInstance(
            'Node',
            'default',
            Node::CONTAINER,
            true
        );
        $this->assertEquals('default', $node->name);
        $this->assertEquals(Node::CONTAINER, $node->type);
        $this->assertEquals(true, $node->is_group);
    }

    /**
     * Test for PhpMyAdmin\Navigation\NodeFactory::getInstance
     *
     * @return void
     */
    public function testFileError()
    {
        try {
            NodeFactory::getInstance('NodeDoesNotExist');
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test for PhpMyAdmin\Navigation\NodeFactory::getInstance
     *
     * @return void
     */
    public function testClassNameError()
    {
        try {
            NodeFactory::getInstance('Invalid');
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }
}
