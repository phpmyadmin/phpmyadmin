<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Navigation\Nodes\NodeTableContainer;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @covers \PhpMyAdmin\Navigation\Nodes\NodeTableContainer
 */
#[CoversClass(NodeTableContainer::class)]
class NodeTableContainerTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
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
        $parent = NodeFactory::getInstance('NodeTableContainer');
        self::assertIsArray($parent->links);
        self::assertSame([
            'text' => ['route' => '/database/structure', 'params' => ['tbl_type' => 'table', 'db' => null]],
            'icon' => ['route' => '/database/structure', 'params' => ['tbl_type' => 'table', 'db' => null]],
        ], $parent->links);
        self::assertSame('tables', $parent->realName);
        self::assertStringContainsString('tableContainer', $parent->classes);
    }
}
