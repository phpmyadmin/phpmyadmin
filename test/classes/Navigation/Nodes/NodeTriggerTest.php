<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Navigation\Nodes\NodeTrigger
 */
class NodeTriggerTest extends AbstractTestCase
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
        $parent = NodeFactory::getInstance('NodeTrigger');
        self::assertIsArray($parent->links);
        self::assertSame([
            'text' => [
                'route' => '/database/triggers',
                'params' => ['edit_item' => 1, 'db' => null, 'item_name' => null],
            ],
            'icon' => [
                'route' => '/database/triggers',
                'params' => ['export_item' => 1, 'db' => null, 'item_name' => null],
            ],
        ], $parent->links);
    }
}
