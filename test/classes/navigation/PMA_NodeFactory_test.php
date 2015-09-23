<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for NodeFactory class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/navigation/NodeFactory.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/php-gettext/gettext.inc';

/**
 * Tests for NodeFactory class
 *
 * @package PhpMyAdmin-test
 */
class NodeFactory_Test extends PHPUnit_Framework_TestCase
{
    /**
     * SetUp for test cases
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['server'] = 0;
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
    }

    /**
     * Test for PMA_NodeFactory::getInstance
     *
     * @return void
     */
    public function testDefaultNode()
    {
        $node = PMA_NodeFactory::getInstance();
        $this->assertEquals('default', $node->name);
        $this->assertEquals(Node::OBJECT, $node->type);
        $this->assertEquals(false, $node->is_group);
    }

    /**
     * Test for PMA_NodeFactory::getInstance
     *
     * @return void
     */
    public function testDefaultContainer()
    {
        $node = PMA_NodeFactory::getInstance('Node', 'default', Node::CONTAINER);
        $this->assertEquals('default', $node->name);
        $this->assertEquals(Node::CONTAINER, $node->type);
        $this->assertEquals(false, $node->is_group);
    }

    /**
     * Test for PMA_NodeFactory::getInstance
     *
     * @return void
     */
    public function testGroupContainer()
    {
        $node = PMA_NodeFactory::getInstance(
            'Node', 'default', Node::CONTAINER, true
        );
        $this->assertEquals('default', $node->name);
        $this->assertEquals(Node::CONTAINER, $node->type);
        $this->assertEquals(true, $node->is_group);
    }

    /**
     * Test for PMA_NodeFactory::getInstance
     *
     * @return void
     */
    public function testFileError()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        PMA_NodeFactory::getInstance('Node_DoesNotExist');
    }

    /**
     * Test for PMA_NodeFactory::getInstance
     *
     * @return void
     */
    public function testClassNameError()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        PMA_NodeFactory::getInstance('Invalid');
    }
}
