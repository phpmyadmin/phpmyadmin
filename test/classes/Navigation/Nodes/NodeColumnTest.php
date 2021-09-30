<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Navigation\Nodes\NodeColumn
 */
class NodeColumnTest extends AbstractTestCase
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
        $parent = NodeFactory::getInstance('NodeColumn', ['name' => 'name', 'key' => 'key']);
        $this->assertIsArray($parent->links);
        $this->assertEquals(
            [
                'text' => [
                    'route' => '/table/structure/change',
                    'params' => ['change_column' => 1, 'db' => null, 'table' => null, 'field' => null],
                ],
                'icon' => [
                    'route' => '/table/structure/change',
                    'params' => ['change_column' => 1, 'db' => null, 'table' => null, 'field' => null],
                ],
                'title' => 'Structure',
            ],
            $parent->links
        );
    }
}
