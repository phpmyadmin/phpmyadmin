<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Navigation\Nodes\NodeTriggerContainer
 */
class NodeTriggerContainerTest extends AbstractTestCase
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
        $parent = NodeFactory::getInstance('NodeTriggerContainer');
        self::assertIsArray($parent->links);
        self::assertSame([
            'text' => ['route' => '/database/triggers', 'params' => ['db' => null, 'table' => null]],
            'icon' => ['route' => '/database/triggers', 'params' => ['db' => null, 'table' => null]],
        ], $parent->links);
        self::assertSame('triggers', $parent->realName);
    }
}
