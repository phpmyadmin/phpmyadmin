<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeViewContainer class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Theme;

/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeViewContainer class
 *
 * @package PhpMyAdmin-test
 */
class NodeViewContainerTest extends PmaTestCase
{
    /**
     * SetUp for test cases
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = true;
        $GLOBALS['cfg']['NavigationTreeDbSeparator'] = '_';
        $GLOBALS['cfg']['NavigationTreeTableSeparator'] = '__';
        $GLOBALS['cfg']['NavigationTreeTableLevel'] = 1;
    }


    /**
     * Test for __construct
     *
     * @return void
     */
    public function testConstructor()
    {
        $parent = NodeFactory::getInstance('NodeViewContainer');
        $this->assertArrayHasKey(
            'text',
            $parent->links
        );
        $this->assertContains(
            'db_structure.php',
            $parent->links['text']
        );
        $this->assertEquals('views', $parent->real_name);
        $this->assertContains('viewContainer', $parent->classes);
    }
}
