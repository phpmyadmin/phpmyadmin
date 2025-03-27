<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Navigation\Nodes\NodeIndex
 */
class NodeIndexTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
    }

    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = NodeFactory::getInstance('NodeIndex');
        self::assertIsArray($parent->links);
        self::assertSame([
            'text' => ['route' => '/table/indexes', 'params' => ['db' => null, 'table' => null, 'index' => null]],
            'icon' => ['route' => '/table/indexes', 'params' => ['db' => null, 'table' => null, 'index' => null]],
        ], $parent->links);
    }
}
