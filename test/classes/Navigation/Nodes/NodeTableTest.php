<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Navigation\Nodes\NodeTable
 */
class NodeTableTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['NavigationTreeDefaultTabTable'] = 'search';
        $GLOBALS['cfg']['NavigationTreeDefaultTabTable2'] = 'insert';
        $GLOBALS['cfg']['DefaultTabTable'] = 'browse';
        $GLOBALS['cfg']['MaxNavigationItems'] = 250;
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = true;
        $GLOBALS['cfg']['NavigationTreeDbSeparator'] = '_';
        $GLOBALS['cfg']['NavigationTreeTableSeparator'] = '__';
        $GLOBALS['cfg']['NavigationTreeTableLevel'] = 1;
    }

    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = NodeFactory::getInstance('NodeTable');
        $this->assertIsArray($parent->links);
        $this->assertEquals(
            [
                'text' => ['route' => '/sql', 'params' => ['pos' => 0, 'db' => null, 'table' => null]],
                'icon' => ['route' => '/table/search', 'params' => ['db' => null, 'table' => null]],
                'second_icon' => ['route' => '/table/change', 'params' => ['db' => null, 'table' => null]],
                'title' => 'Browse',
            ],
            $parent->links
        );
        $this->assertStringContainsString('table', $parent->classes);
    }

    /**
     * Tests whether the node icon is properly set based on the icon target.
     *
     * @param string $target    target of the icon
     * @param string $imageName name of the image that should be set
     *
     * @dataProvider providerForTestIcon
     */
    public function testIcon(string $target, string $imageName, string $imageTitle): void
    {
        $GLOBALS['cfg']['NavigationTreeDefaultTabTable'] = $target;
        $node = NodeFactory::getInstance('NodeTable');
        $this->assertEquals($imageName, $node->icon['image']);
        $this->assertEquals($imageTitle, $node->icon['title']);
    }

    /**
     * Data provider for testIcon().
     *
     * @return array data for testIcon()
     */
    public function providerForTestIcon(): array
    {
        return [
            ['structure', 'b_props', 'Structure'],
            ['search', 'b_search', 'Search'],
            ['insert', 'b_insrow', 'Insert'],
            ['sql', 'b_sql', 'SQL'],
            ['browse', 'b_browse', 'Browse'],
        ];
    }
}
