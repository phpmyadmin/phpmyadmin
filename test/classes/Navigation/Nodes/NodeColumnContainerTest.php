<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\Nodes\NodeColumnContainer;
use PhpMyAdmin\Tests\AbstractTestCase;

/** @covers \PhpMyAdmin\Navigation\Nodes\NodeColumnContainer */
class NodeColumnContainerTest extends AbstractTestCase
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

    public function testConstructor(): void
    {
        $parent = new NodeColumnContainer();
        $this->assertEquals(
            [
                'text' => ['route' => '/table/structure', 'params' => ['db' => null, 'table' => null]],
                'icon' => ['route' => '/table/structure', 'params' => ['db' => null, 'table' => null]],
            ],
            $parent->links,
        );
        $this->assertEquals('columns', $parent->realName);
    }
}
