<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Node_Table class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/navigation/NodeFactory.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';

/**
 * Tests for Node_Table class
 *
 * @package PhpMyAdmin-test
 */
class Node_Table_Test extends PHPUnit_Framework_TestCase
{
    /**
     * SetUp for test cases
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['token'] = 'token';
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
    }


    /**
     * Test for __construct
     *
     * @return void
     */
    public function testConstructor()
    {
        $parent = PMA_NodeFactory::getInstance('Node_Table');
        $this->assertArrayHasKey(
            'text',
            $parent->links
        );
        $this->assertContains(
            'sql.php',
            $parent->links['text']
        );
        $this->assertContains('table', $parent->classes);
    }

    /**
     * Tests whether the node icon is properly set based on the icon target.
     *
     * @param string $target    target of the icon
     * @param string $imageName name of the image that should be set
     *
     * @return void
     * @dataProvider providerForTestIcon
     */
    public function testIcon($target, $imageName)
    {
        $GLOBALS['cfg']['NavigationTreeDefaultTabTable'] = $target;
        $node = PMA_NodeFactory::getInstance('Node_Table');
        $this->assertContains($imageName, $node->icon);
    }

    /**
     * Data provider for testIcon().
     *
     * @return array data for testIcon()
     */
    public function providerForTestIcon()
    {
        return array(
            array('tbl_structure.php', 'b_props'),
            array('tbl_select.php', 'b_search'),
            array('tbl_change.php', 'b_insrow'),
            array('tbl_sql.php', 'b_sql'),
            array('sql.php', 'b_browse'),
        );
    }
}
?>
