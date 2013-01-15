<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for NodeFactory class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/navigation/NodeFactory.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';


class NodeFactory_test extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['token'] = 'token';
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        if (! function_exists('__')) {
            function __($str)
            {
                return $str;
            }
        }
    }

    public function testDefaultNode()
    {
        $node = PMA_NodeFactory::getInstance();
        $this->assertEquals('default', $node->name);
        $this->assertEquals(Node::OBJECT, $node->type);
        $this->assertEquals(false, $node->is_group);
    }

    public function testDefaultContainer()
    {
        $node = PMA_NodeFactory::getInstance('Node', 'default', Node::CONTAINER);
        $this->assertEquals('default', $node->name);
        $this->assertEquals(Node::CONTAINER, $node->type);
        $this->assertEquals(false, $node->is_group);
    }

    public function testGroupContainer()
    {
        $node = PMA_NodeFactory::getInstance(
            'Node', 'default', Node::CONTAINER, true
        );
        $this->assertEquals('default', $node->name);
        $this->assertEquals(Node::CONTAINER, $node->type);
        $this->assertEquals(true, $node->is_group);
    }

    public function testFileError()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        PMA_NodeFactory::getInstance('Node_DoesNotExist');
    }

    public function testClassNameError()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        PMA_NodeFactory::getInstance('Invalid');
    }
}
?>
