<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\Nodes\NodeProcedure;
use PhpMyAdmin\Tests\AbstractTestCase;

/** @covers \PhpMyAdmin\Navigation\Nodes\NodeProcedure */
class NodeProcedureTest extends AbstractTestCase
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
        $parent = new NodeProcedure('default');
        $this->assertEquals(
            [
                'text' => [
                    'route' => '/database/routines',
                    'params' => ['item_type' => 'PROCEDURE', 'edit_item' => 1, 'db' => null, 'item_name' => null],
                ],
                'icon' => [
                    'route' => '/database/routines',
                    'params' => ['item_type' => 'PROCEDURE', 'execute_dialog' => 1, 'db' => null, 'item_name' => null],
                ],
            ],
            $parent->links,
        );
    }
}
