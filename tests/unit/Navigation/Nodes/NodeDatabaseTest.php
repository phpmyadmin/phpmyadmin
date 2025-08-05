<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Navigation\Nodes\NodeDatabase;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\UserPrivileges;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeDatabase::class)]
class NodeDatabaseTest extends AbstractTestCase
{
    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $parent = new NodeDatabase(new Config(), 'default');
        self::assertSame('/database/structure', $parent->link->route);
        self::assertSame(['db' => null], $parent->link->params);
        self::assertSame('Structure', $parent->link->title);
        self::assertSame('/database/operations', $parent->icon->route);
        self::assertSame(['db' => null], $parent->icon->params);
        self::assertStringContainsString('database', $parent->classes);
    }

    /**
     * Test for getPresence
     */
    public function testGetPresence(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;
        DatabaseInterface::$instance = $this->createDatabaseInterface();
        $userPrivileges = new UserPrivileges();

        $parent = new NodeDatabase($config, 'default');
        self::assertSame(
            2,
            $parent->getPresence($userPrivileges, 'tables'),
        );
        self::assertSame(
            0,
            $parent->getPresence($userPrivileges, 'views'),
        );
        self::assertSame(
            1,
            $parent->getPresence($userPrivileges, 'functions'),
        );
        self::assertSame(
            0,
            $parent->getPresence($userPrivileges, 'procedures'),
        );
        self::assertSame(
            0,
            $parent->getPresence($userPrivileges, 'events'),
        );
    }

    /**
     * Test for getData
     */
    public function testGetData(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;
        DatabaseInterface::$instance = $this->createDatabaseInterface();
        $userPrivileges = new UserPrivileges();

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::NAV_WORK => true,
            RelationParameters::NAVIGATION_HIDING => 'navigationhiding',
        ]);

        $parent = new NodeDatabase($config, 'default');

        $tables = $parent->getData($userPrivileges, $relationParameters, 'tables', 0);
        self::assertContains('test1', $tables);
        self::assertContains('test2', $tables);

        $views = $parent->getData($userPrivileges, $relationParameters, 'views', 0);
        self::assertEmpty($views);

        $functions = $parent->getData($userPrivileges, $relationParameters, 'functions', 0);
        self::assertContains('testFunction', $functions);
        self::assertCount(1, $functions);

        self::assertEmpty($parent->getData($userPrivileges, $relationParameters, 'procedures', 0));
        self::assertEmpty($parent->getData($userPrivileges, $relationParameters, 'events', 0));
    }

    /**
     * Test for setHiddenCount and getHiddenCount
     */
    public function testHiddenCount(): void
    {
        $parent = new NodeDatabase(new Config(), 'default');
        $parent->setHiddenCount(1);
        self::assertSame(1, $parent->getHiddenCount());
        $parent->setHiddenCount(0);
        self::assertSame(0, $parent->getHiddenCount());
        $parent->setHiddenCount(-1);
        self::assertSame(0, $parent->getHiddenCount());
    }
}
