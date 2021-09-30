<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Navigation\Nodes\NodeProcedure
 */
class NodeProcedureTest extends AbstractTestCase
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
        $parent = NodeFactory::getInstance('NodeProcedure');
        $this->assertIsArray($parent->links);
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
            $parent->links
        );
    }
}
