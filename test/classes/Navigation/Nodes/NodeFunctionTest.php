<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\Nodes\NodeFunction;
use PhpMyAdmin\Tests\AbstractTestCase;

/** @covers \PhpMyAdmin\Navigation\Nodes\NodeFunction */
class NodeFunctionTest extends AbstractTestCase
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
        $parent = new NodeFunction('default');
        $this->assertEquals(
            [
                'text' => [
                    'route' => '/database/routines',
                    'params' => ['item_type' => 'FUNCTION', 'edit_item' => 1, 'db' => null, 'item_name' => null],
                ],
                'icon' => [
                    'route' => '/database/routines',
                    'params' => ['item_type' => 'FUNCTION', 'execute_dialog' => 1, 'db' => null, 'item_name' => null],
                ],
            ],
            $parent->links,
        );
    }
}
