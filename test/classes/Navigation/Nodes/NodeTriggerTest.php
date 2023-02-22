<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\Nodes\NodeTrigger;
use PhpMyAdmin\Tests\AbstractTestCase;

/** @covers \PhpMyAdmin\Navigation\Nodes\NodeTrigger */
class NodeTriggerTest extends AbstractTestCase
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
        $parent = new NodeTrigger('default');
        $this->assertEquals(
            [
                'text' => [
                    'route' => '/database/triggers',
                    'params' => ['edit_item' => 1, 'db' => null, 'item_name' => null],
                ],
                'icon' => [
                    'route' => '/database/triggers',
                    'params' => ['export_item' => 1, 'db' => null, 'item_name' => null],
                ],
            ],
            $parent->links,
        );
    }
}
