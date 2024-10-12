<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Navigation\Nodes\NodeDatabase;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Navigation\Nodes\NodeDatabase
 */
class NodeDatabaseTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['DefaultTabDatabase'] = 'structure';
        $GLOBALS['cfg']['MaxNavigationItems'] = 250;
        $GLOBALS['cfg']['Server'] = [];
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
    }

    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = NodeFactory::getInstance('NodeDatabase');
        self::assertIsArray($parent->links);
        self::assertSame([
            'text' => [
                'route' => '/database/structure',
                'params' => ['db' => null],
            ],
            'icon' => ['route' => '/database/operations', 'params' => ['db' => null]],
            'title' => 'Structure',
        ], $parent->links);
        self::assertStringContainsString('database', $parent->classes);
    }

    /**
     * Test for getPresence
     */
    public function testGetPresence(): void
    {
        $parent = NodeFactory::getInstance('NodeDatabase');
        self::assertSame(2, $parent->getPresence('tables'));
        self::assertSame(0, $parent->getPresence('views'));
        self::assertSame(1, $parent->getPresence('functions'));
        self::assertSame(0, $parent->getPresence('procedures'));
        self::assertSame(0, $parent->getPresence('events'));
    }

    /**
     * Test for getData
     */
    public function testGetData(): void
    {
        $parent = NodeFactory::getInstance('NodeDatabase');

        $tables = $parent->getData('tables', 0);
        self::assertContains('test1', $tables);
        self::assertContains('test2', $tables);

        $views = $parent->getData('views', 0);
        self::assertEmpty($views);

        $functions = $parent->getData('functions', 0);
        self::assertContains('testFunction', $functions);
        self::assertCount(1, $functions);

        self::assertEmpty($parent->getData('procedures', 0));
        self::assertEmpty($parent->getData('events', 0));
    }

    /**
     * Test for setHiddenCount and getHiddenCount
     */
    public function testHiddenCount(): void
    {
        /** @var NodeDatabase $parent */
        $parent = NodeFactory::getInstance('NodeDatabase');

        $parent->setHiddenCount(3);
        self::assertSame(3, $parent->getHiddenCount());
    }
}
