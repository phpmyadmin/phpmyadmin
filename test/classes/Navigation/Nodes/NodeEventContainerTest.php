<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Navigation\Nodes\NodeEventContainer
 */
class NodeEventContainerTest extends AbstractTestCase
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
        $parent = NodeFactory::getInstance('NodeEventContainer');
        $this->assertIsArray($parent->links);
        $this->assertEquals(
            [
                'text' => ['route' => '/database/events', 'params' => ['db' => null]],
                'icon' => ['route' => '/database/events', 'params' => ['db' => null]],
            ],
            $parent->links
        );
        $this->assertEquals('events', $parent->realName);
    }
}
