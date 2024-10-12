<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Navigation\Nodes\NodeColumnContainer
 */
class NodeColumnContainerTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
    }

    public function testConstructor(): void
    {
        $parent = NodeFactory::getInstance('NodeColumnContainer');
        self::assertIsArray($parent->links);
        self::assertEquals([
            'text' => ['route' => '/table/structure', 'params' => ['db' => null, 'table' => null]],
            'icon' => ['route' => '/table/structure', 'params' => ['db' => null, 'table' => null]],
        ], $parent->links);
        self::assertEquals('columns', $parent->realName);
    }
}
