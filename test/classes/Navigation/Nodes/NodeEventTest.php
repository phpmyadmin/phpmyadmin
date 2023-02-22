<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\Nodes\NodeEvent;
use PhpMyAdmin\Tests\AbstractTestCase;

/** @covers \PhpMyAdmin\Navigation\Nodes\NodeEvent */
class NodeEventTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $GLOBALS['server'] = 0;
    }

    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = new NodeEvent('default');
        $this->assertEquals(
            [
                'text' => [
                    'route' => '/database/events',
                    'params' => ['edit_item' => 1, 'db' => null, 'item_name' => null],
                ],
                'icon' => [
                    'route' => '/database/events',
                    'params' => ['export_item' => 1, 'db' => null, 'item_name' => null],
                ],
            ],
            $parent->links,
        );
    }
}
