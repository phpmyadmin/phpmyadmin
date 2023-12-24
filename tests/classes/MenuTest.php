<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Menu;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Menu::class)]
class MenuTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;

        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->selectedServer['verbose'] = 'verbose host';
        Current::$database = 'pma_test';
        Current::$table = 'table1';
    }

    /**
     * Server menu test
     */
    public function testServer(): void
    {
        $menu = new Menu($this->dbi, new Template(), '', '');
        $this->assertStringContainsString(
            'floating_menubar',
            $menu->getDisplay(),
        );
    }

    /**
     * Database menu test
     */
    public function testDatabase(): void
    {
        $menu = new Menu($this->dbi, new Template(), 'pma_test', '');
        $this->assertStringContainsString(
            'floating_menubar',
            $menu->getDisplay(),
        );
    }

    /**
     * Table menu test
     */
    public function testTable(): void
    {
        $menu = new Menu($this->dbi, new Template(), 'pma_test', 'table1');
        $this->assertStringContainsString(
            'floating_menubar',
            $menu->getDisplay(),
        );
    }

    /**
     * Table menu setTable test
     */
    public function testSetTable(): void
    {
        $menu = new Menu($this->dbi, new Template(), 'pma_test', '');
        $menu->setTable('table1');
        $this->assertStringContainsString(
            'table1',
            $menu->getDisplay(),
        );
    }
}
