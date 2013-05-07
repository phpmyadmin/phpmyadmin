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


class Node_Test extends PHPUnit_Framework_TestCase
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

    /**
     * Tests whether Node->hasSiblings() method returns false
     * when the node does not have any siblings.
     *
     * @return void
     * @test
     */
    public function testHasSiblingsWithNoSiblings()
    {
        $parent = PMA_NodeFactory::getInstance();
        $child = PMA_NodeFactory::getInstance();
        $parent->addChild($child);
        $this->assertEquals(false, $child->hasSiblings());
    }

    /**
     * Tests whether Node->hasSiblings() method returns true
     * when it actually has siblings.
     *
     * @return void
     * @test
     */
    public function testHasSiblingsWithSiblings()
    {
        $parent = PMA_NodeFactory::getInstance();
        $firstChild = PMA_NodeFactory::getInstance();
        $parent->addChild($firstChild);
        $secondChild = PMA_NodeFactory::getInstance();
        $parent->addChild($secondChild);
        // Normal case; two Node:NODE type siblings
        $this->assertEquals(true, $firstChild->hasSiblings());

        $parent = PMA_NodeFactory::getInstance();
        $firstChild = PMA_NodeFactory::getInstance();
        $parent->addChild($firstChild);
        $secondChild = PMA_NodeFactory::getInstance(
            'Node', 'default', Node::CONTAINER
        );
        $parent->addChild($secondChild);
        // Empty Node::CONTAINER type node should not be considered in hasSiblings()
        $this->assertEquals(false, $firstChild->hasSiblings());

        $grandChild = PMA_NodeFactory::getInstance();
        $secondChild->addChild($grandChild);
        // Node::CONTAINER type nodes with children are counted for hasSiblings()
        $this->assertEquals(true, $firstChild->hasSiblings());
    }

    /**
     * It is expected that Node->hasSiblings() method always return true
     * for Nodes that are 3 levels deep (columns and indexes).
     *
     * @return void
     * @test
     */
    public function testHasSiblingsForNodesAtLevelThree()
    {
        $parent = PMA_NodeFactory::getInstance();
        $child = PMA_NodeFactory::getInstance();
        $parent->addChild($child);
        $grandChild = PMA_NodeFactory::getInstance();
        $child->addChild($grandChild);
        $greatGrandChild = PMA_NodeFactory::getInstance();
        $grandChild->addChild($greatGrandChild);

        // Should return false for node that are two levels deeps
        $this->assertEquals(false, $grandChild->hasSiblings());
        // Should return true for node that are three levels deeps
        $this->assertEquals(true, $greatGrandChild->hasSiblings());
    }

    public function testComment()
    {
        // A non-qualified Node shouldn't have a comment
        $this->assertEquals(
            PMA_NodeFactory::getInstance()->getComment(),
            ''
        );
    }
}
?>
