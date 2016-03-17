<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for NodeFactory class
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\navigation\NodeFactory;
use PMA\libraries\navigation\nodes\Node;
use PMA\libraries\Theme;

require_once 'libraries/navigation/NodeFactory.php';
require_once 'test/PMATestCase.php';

/**
 * Tests for NodeFactory class
 *
 * @package PhpMyAdmin-test
 */
class NodeFactoryTest extends PMATestCase
{
    /**
     * SetUp for test cases
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['server'] = 0;
        $_SESSION['PMA_Theme'] = Theme::load('./themes/pmahomme');
    }

    /**
     * Test for PMA\libraries\navigation\NodeFactory::getInstance
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
     * Test for PMA\libraries\navigation\NodeFactory::getInstance
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
     * Test for PMA\libraries\navigation\NodeFactory::getInstance
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
     * Test for PMA\libraries\navigation\NodeFactory::getInstance
     *
     * @return void
     */
    public function testFileError()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        NodeFactory::getInstance('NodeDoesNotExist');
    }

    /**
     * Test for PMA\libraries\navigation\NodeFactory::getInstance
     *
     * @return void
     */
    public function testClassNameError()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        NodeFactory::getInstance('Invalid');
    }
}
