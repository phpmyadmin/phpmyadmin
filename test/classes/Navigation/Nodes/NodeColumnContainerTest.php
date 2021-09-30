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
        $this->assertIsArray($parent->links);
        $this->assertEquals(
            [
                'text' => ['route' => '/table/structure', 'params' => ['db' => null, 'table' => null]],
                'icon' => ['route' => '/table/structure', 'params' => ['db' => null, 'table' => null]],
            ],
            $parent->links
        );
        $this->assertEquals('columns', $parent->realName);
    }
}
