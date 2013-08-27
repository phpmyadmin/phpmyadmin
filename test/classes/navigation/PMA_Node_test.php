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


class Node_test extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['token'] = 'token';
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
    }

    public function testAddNode()
    {
        $parent = PMA_NodeFactory::getInstance('Node', 'parent');
        $child = PMA_NodeFactory::getInstance('Node', 'child');
        $parent->addChild($child);
        $this->assertEquals(
            $parent->getChild($child->name),
            $child
        );
        $this->assertEquals(
            $parent->getChild($child->real_name, true),
            $child
        );
    }

    public function testGetChildError()
    {
        $parent = PMA_NodeFactory::getInstance('Node', 'parent');
        $this->assertEquals(
            $parent->getChild("foo"),
            false
        );
        $this->assertEquals(
            $parent->getChild("foo", true),
            false
        );
    }

    public function testRemoveNode()
    {
        $parent = PMA_NodeFactory::getInstance('Node', 'parent');
        $child = PMA_NodeFactory::getInstance('Node', 'child');
        $parent->addChild($child);
        $this->assertEquals(
            $parent->getChild($child->name),
            $child
        );
        $parent->removeChild($child->name);
        $this->assertEquals(
            $parent->getChild($child->name),
            false
        );
    }

    public function testNodeHasChildren()
    {
        $parent = PMA_NodeFactory::getInstance();
        $empty_container = PMA_NodeFactory::getInstance('Node', 'empty', Node::CONTAINER);
        $child = PMA_NodeFactory::getInstance();
        // test with no children
        $this->assertEquals(
            $parent->hasChildren(true),
            false
        );
        $this->assertEquals(
            $parent->hasChildren(false),
            false
        );
        // test with an empty container
        $parent->addChild($empty_container);
        $this->assertEquals(
            $parent->hasChildren(true),
            true
        );
        $this->assertEquals(
            $parent->hasChildren(false),
            false
        );
        // test with a real child
        $parent->addChild($child);
        $this->assertEquals(
            $parent->hasChildren(true),
            true
        );
        $this->assertEquals(
            $parent->hasChildren(false),
            true
        );
    }

    public function testNumChildren()
    {
        // start with root node only
        $parent = PMA_NodeFactory::getInstance();
        $this->assertEquals($parent->numChildren(), 0);
        // add a child
        $child = PMA_NodeFactory::getInstance();
        $parent->addChild($child);
        $this->assertEquals($parent->numChildren(), 1);
        // add a direct grandchild, this one doesn't count as
        // it's not enclosed in a CONTAINER
        $child->addChild(PMA_NodeFactory::getInstance());
        $this->assertEquals($parent->numChildren(), 1);
        // add a container, this one doesn't count wither
        $container = PMA_NodeFactory::getInstance('Node', 'default', Node::CONTAINER);
        $parent->addChild($container);
        $this->assertEquals($parent->numChildren(), 1);
        // add a grandchild to container, this one counts
        $container->addChild(PMA_NodeFactory::getInstance());
        $this->assertEquals($parent->numChildren(), 2);
        // add another grandchild to container, this one counts
        $container->addChild(PMA_NodeFactory::getInstance());
        $this->assertEquals($parent->numChildren(), 3);
    }

    public function testParents()
    {
        $parent = PMA_NodeFactory::getInstance();
        $this->assertEquals($parent->parents(), array()); // exclude self
        $this->assertEquals($parent->parents(true), array($parent)); // include self

        $child = PMA_NodeFactory::getInstance();
        $parent->addChild($child);

        $this->assertEquals($child->parents(), array($parent)); // exclude self
        $this->assertEquals($child->parents(true), array($child, $parent)); // include self
    }

    public function testRealParent()
    {
        $parent = PMA_NodeFactory::getInstance();
        $this->assertEquals($parent->realParent(), false);

        $child = PMA_NodeFactory::getInstance();
        $parent->addChild($child);
        $this->assertEquals($child->realParent(), $parent);
    }

    public function testHasSiblings()
    {
        $parent = PMA_NodeFactory::getInstance();
        $child = PMA_NodeFactory::getInstance();
        $parent->addChild($child);
        $this->assertEquals($child->hasSiblings(), false);
    }
}
?>
