<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Navigation\Nodes\NodeFunctionContainer
 */
class NodeFunctionContainerTest extends AbstractTestCase
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
        $parent = NodeFactory::getInstance('NodeFunctionContainer');
        self::assertIsArray($parent->links);
        self::assertSame([
            'text' => ['route' => '/database/routines', 'params' => ['type' => 'FUNCTION', 'db' => null]],
            'icon' => ['route' => '/database/routines', 'params' => ['type' => 'FUNCTION', 'db' => null]],
        ], $parent->links);
        self::assertSame('functions', $parent->realName);
    }
}
