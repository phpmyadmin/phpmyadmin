<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\Nodes\NodeFunctionContainer;
use PhpMyAdmin\Tests\AbstractTestCase;

/** @covers \PhpMyAdmin\Navigation\Nodes\NodeFunctionContainer */
class NodeFunctionContainerTest extends AbstractTestCase
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
        $parent = new NodeFunctionContainer();
        $this->assertEquals(
            [
                'text' => ['route' => '/database/routines', 'params' => ['type' => 'FUNCTION', 'db' => null]],
                'icon' => ['route' => '/database/routines', 'params' => ['type' => 'FUNCTION', 'db' => null]],
            ],
            $parent->links,
        );
        $this->assertEquals('functions', $parent->realName);
    }
}
