<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Navigation\Nodes\NodeDatabase;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeDatabase::class)]
class NodeDatabaseTest extends AbstractTestCase
{
    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = new NodeDatabase('default');
        $this->assertEquals(
            [
                'text' => ['route' => '/database/structure', 'params' => ['db' => null]],
                'icon' => ['route' => '/database/operations', 'params' => ['db' => null]],
                'title' => 'Structure',
            ],
            $parent->links,
        );
        $this->assertStringContainsString('database', $parent->classes);
    }

    /**
     * Test for getPresence
     */
    public function testGetPresence(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = true;
        DatabaseInterface::$instance = $this->createDatabaseInterface();

        $parent = new NodeDatabase('default');
        $this->assertEquals(
            2,
            $parent->getPresence('tables'),
        );
        $this->assertEquals(
            0,
            $parent->getPresence('views'),
        );
        $this->assertEquals(
            1,
            $parent->getPresence('functions'),
        );
        $this->assertEquals(
            0,
            $parent->getPresence('procedures'),
        );
        $this->assertEquals(
            0,
            $parent->getPresence('events'),
        );
    }

    /**
     * Test for getData
     */
    public function testGetData(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = true;
        DatabaseInterface::$instance = $this->createDatabaseInterface();

        $relationParameters = RelationParameters::fromArray([
            'db' => 'pmadb',
            'navwork' => true,
            'navigationhiding' => 'navigationhiding',
        ]);

        $parent = new NodeDatabase('default');

        $tables = $parent->getData($relationParameters, 'tables', 0);
        $this->assertContains('test1', $tables);
        $this->assertContains('test2', $tables);

        $views = $parent->getData($relationParameters, 'views', 0);
        $this->assertEmpty($views);

        $functions = $parent->getData($relationParameters, 'functions', 0);
        $this->assertContains('testFunction', $functions);
        $this->assertCount(1, $functions);

        $this->assertEmpty($parent->getData($relationParameters, 'procedures', 0));
        $this->assertEmpty($parent->getData($relationParameters, 'events', 0));
    }

    /**
     * Test for setHiddenCount and getHiddenCount
     */
    public function testHiddenCount(): void
    {
        $parent = new NodeDatabase('default');
        $parent->setHiddenCount(1);
        $this->assertSame(1, $parent->getHiddenCount());
        $parent->setHiddenCount(0);
        $this->assertSame(0, $parent->getHiddenCount());
        $parent->setHiddenCount(-1);
        $this->assertSame(0, $parent->getHiddenCount());
    }
}
