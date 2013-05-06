<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for NodeFactory class and Node_Column class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/navigation/NodeFactory.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';


class Node_Index_Test extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['token'] = 'token';
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
    }

    public function testConstructor()
    {
        $parent = PMA_NodeFactory::getInstance('Node_Index');
        $this->assertArrayHasKey(
            'text',
            $parent->links
        );
        $this->assertContains(
            'tbl_indexes.php',
            $parent->links['text']
        );
    }

    public function testAddNode()
    {
        $parent = PMA_NodeFactory::getInstance('Node_Index', 'parent');
        $child = PMA_NodeFactory::getInstance('Node_Index', 'child');
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
}
?>
