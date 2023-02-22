<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\Nodes\NodeIndexContainer;
use PhpMyAdmin\Tests\AbstractTestCase;

/** @covers \PhpMyAdmin\Navigation\Nodes\NodeIndexContainer */
class NodeIndexContainerTest extends AbstractTestCase
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
        $parent = new NodeIndexContainer();
        $this->assertEquals(
            [
                'text' => ['route' => '/table/structure', 'params' => ['db' => null, 'table' => null]],
                'icon' => ['route' => '/table/structure', 'params' => ['db' => null, 'table' => null]],
            ],
            $parent->links,
        );
        $this->assertEquals('indexes', $parent->realName);
    }
}
