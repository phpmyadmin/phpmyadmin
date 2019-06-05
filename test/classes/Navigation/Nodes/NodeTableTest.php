<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeTable class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Theme;

/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeTable class
 *
 * @package PhpMyAdmin-test
 */
class NodeTableTest extends PmaTestCase
{
    /**
     * SetUp for test cases
     *
     * @return void
     */
    protected function setUp(): void
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
    }


    /**
     * Test for __construct
     *
     * @return void
     */
    public function testConstructor()
    {
        $parent = NodeFactory::getInstance('NodeTable');
        $this->assertArrayHasKey(
            'text',
            $parent->links
        );
        $this->assertStringContainsString(
            'sql.php',
            $parent->links['text']
        );
        $this->assertStringContainsString('table', $parent->classes);
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
    public function testIcon($target, $imageName): void
    {
        $GLOBALS['cfg']['NavigationTreeDefaultTabTable'] = $target;
        $node = NodeFactory::getInstance('NodeTable');
        $this->assertStringContainsString($imageName, $node->icon[0]);
    }

    /**
     * Data provider for testIcon().
     *
     * @return array data for testIcon()
     */
    public function providerForTestIcon()
    {
        return [
            [
                'structure',
                'b_props',
            ],
            [
                'search',
                'b_search',
            ],
            [
                'insert',
                'b_insrow',
            ],
            [
                'sql',
                'b_sql',
            ],
            [
                'browse',
                'b_browse',
            ],
        ];
    }
}
