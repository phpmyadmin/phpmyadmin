<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\Nodes\NodeIndex;
use PhpMyAdmin\Tests\AbstractTestCase;

/** @covers \PhpMyAdmin\Navigation\Nodes\NodeIndex */
class NodeIndexTest extends AbstractTestCase
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
        $parent = new NodeIndex('default');
        $this->assertEquals(
            [
                'text' => ['route' => '/table/indexes', 'params' => ['db' => null, 'table' => null, 'index' => null]],
                'icon' => ['route' => '/table/indexes', 'params' => ['db' => null, 'table' => null, 'index' => null]],
            ],
            $parent->links,
        );
    }
}
