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
require_once 'libraries/php-gettext/gettext.inc';

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
        $GLOBALS['cfg']['NavigationTreeDefaultTabTable'] = 'b_browse';
        $GLOBALS['cfg']['NavigationTreeDefaultTabTable2'] = '';
        $GLOBALS['cfg']['DefaultTabTable'] = 'browse';
        $GLOBALS['cfg']['MaxNavigationItems'] = 250;
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = true;
        $GLOBALS['cfg']['NavigationTreeDbSeparator'] = '_';
        $GLOBALS['cfg']['NavigationTreeTableSeparator'] = '__';
        $GLOBALS['cfg']['NavigationTreeTableLevel'] = 1;
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
        $this->assertContains($imageName, $node->icon[0]);
    }

    /**
     * Data provider for testIcon().
     *
     * @return array data for testIcon()
     */
    public function providerForTestIcon()
    {
        return array(
            array('structure', 'b_props'),
            array('search', 'b_search'),
            array('insert', 'b_insrow'),
            array('sql', 'b_sql'),
            array('browse', 'b_browse'),
        );
    }
}
