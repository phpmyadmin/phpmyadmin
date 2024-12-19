<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Menu;
use PhpMyAdmin\Template;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Menu::class)]
final class MenuTest extends AbstractTestCase
{
    /**
     * Server menu test
     */
    public function testServer(): void
    {
        $menu = $this->createMenu('', '');
        self::assertStringContainsString(
            'floating_menubar',
            $menu->getDisplay(),
        );
    }

    /**
     * Database menu test
     */
    public function testDatabase(): void
    {
        $menu = $this->createMenu('pma_test', '');
        self::assertStringContainsString(
            'floating_menubar',
            $menu->getDisplay(),
        );
    }

    /**
     * Table menu test
     */
    public function testTable(): void
    {
        $menu = $this->createMenu('pma_test', 'table1');
        self::assertStringContainsString(
            'floating_menubar',
            $menu->getDisplay(),
        );
    }

    /**
     * Table menu setTable test
     */
    public function testSetTable(): void
    {
        $menu = $this->createMenu('pma_test', '');
        $menu->setTable('table1');
        self::assertStringContainsString(
            'table1',
            $menu->getDisplay(),
        );
    }

    private function createMenu(string $db, string $table): Menu
    {
        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;

        return new Menu($dbi, new Template(), new Config(), new Relation($dbi), $db, $table);
    }
}
