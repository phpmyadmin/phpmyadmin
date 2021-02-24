<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Core;
use PhpMyAdmin\Menu;
use function define;
use function defined;

class MenuTest extends AbstractTestCase
{
    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::setTheme();
        parent::loadDefaultConfig();

        if (! defined('PMA_IS_WINDOWS')) {
            define('PMA_IS_WINDOWS', false);
        }
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
        $menu = new Menu('', '');
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
        $menu = new Menu('pma_test', '');
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
        $menu = new Menu('pma_test', 'table1');
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
        $menu = new Menu('pma_test', '');
        $menu->setTable('table1');
        $this->assertStringContainsString(
            'table1',
            $menu->getDisplay()
        );
    }
}
