<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Core;
use PhpMyAdmin\Menu;

/**
 * @covers \PhpMyAdmin\Menu
 */
class MenuTest extends AbstractTestCase
{
    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setTheme();

        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['Server']['verbose'] = 'verbose host';
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['server'] = 'server';
        $GLOBALS['db'] = 'pma_test';
        $GLOBALS['table'] = 'table1';
    }

    /**
     * Server menu test
     */
    public function testServer(): void
    {
        $menu = new Menu($this->dbi, '', '');
        $this->assertStringContainsString(
            'floating_menubar',
            $menu->getDisplay()
        );
    }

    /**
     * Database menu test
     */
    public function testDatabase(): void
    {
        $menu = new Menu($this->dbi, 'pma_test', '');
        $this->assertStringContainsString(
            'floating_menubar',
            $menu->getDisplay()
        );
    }

    /**
     * Table menu test
     */
    public function testTable(): void
    {
        $menu = new Menu($this->dbi, 'pma_test', 'table1');
        $this->assertStringContainsString(
            'floating_menubar',
            $menu->getDisplay()
        );
    }

    /**
     * Table menu setTable test
     */
    public function testSetTable(): void
    {
        $menu = new Menu($this->dbi, 'pma_test', '');
        $menu->setTable('table1');
        $this->assertStringContainsString(
            'table1',
            $menu->getDisplay()
        );
    }
}
