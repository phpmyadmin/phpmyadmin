<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Navigation\Nodes\NodeProcedureContainer
 */
class NodeProcedureContainerTest extends AbstractTestCase
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
        $parent = NodeFactory::getInstance('NodeProcedureContainer');
        $this->assertIsArray($parent->links);
        $this->assertEquals(
            [
                'text' => ['route' => '/database/routines', 'params' => ['type' => 'PROCEDURE', 'db' => null]],
                'icon' => ['route' => '/database/routines', 'params' => ['type' => 'PROCEDURE', 'db' => null]],
            ],
            $parent->links
        );
        $this->assertEquals('procedures', $parent->realName);
    }
}
