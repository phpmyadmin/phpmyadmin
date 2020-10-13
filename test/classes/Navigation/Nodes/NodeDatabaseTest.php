<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Navigation\Nodes\NodeDatabase;
use PhpMyAdmin\Tests\AbstractTestCase;

class NodeDatabaseTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::loadDefaultConfig();
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
        $this->assertArrayHasKey(
            'text',
            $parent->links
        );
        $this->assertStringContainsString(
            'index.php?route=/database/structure',
            $parent->links['text']
        );
        $this->assertStringContainsString('database', $parent->classes);
    }

    /**
     * Test for getPresence
     */
    public function testGetPresence(): void
    {
        $parent = NodeFactory::getInstance('NodeDatabase');
        $this->assertEquals(
            2,
            $parent->getPresence('tables')
        );
        $this->assertEquals(
            0,
            $parent->getPresence('views')
        );
        $this->assertEquals(
            1,
            $parent->getPresence('functions')
        );
        $this->assertEquals(
            0,
            $parent->getPresence('procedures')
        );
        $this->assertEquals(
            0,
            $parent->getPresence('events')
        );
    }

    /**
     * Test for getData
     */
    public function testGetData(): void
    {
        $parent = NodeFactory::getInstance('NodeDatabase');

        $tables = $parent->getData('tables', 0);
        $this->assertContains(
            'test1',
            $tables
        );
        $this->assertContains(
            'test2',
            $tables
        );

        $views = $parent->getData('views', 0);
        $this->assertEmpty($views);

        $functions = $parent->getData('functions', 0);
        $this->assertContains(
            'testFunction',
            $functions
        );
        $this->assertCount(1, $functions);

        $this->assertEmpty($parent->getData('procedures', 0));
        $this->assertEmpty($parent->getData('events', 0));
    }

    /**
     * Test for setHiddenCount and getHiddenCount
     */
    public function testHiddenCount(): void
    {
        /** @var NodeDatabase $parent */
        $parent = NodeFactory::getInstance('NodeDatabase');

        $parent->setHiddenCount(3);
        $this->assertEquals(
            3,
            $parent->getHiddenCount()
        );
    }
}
