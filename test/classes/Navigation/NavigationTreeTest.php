<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PhpMyAdmin\Navigation\NavigationTree class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\NavigationTree;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PhpMyAdmin\Navigation\NavigationTree class
 *
 * @package PhpMyAdmin-test
 */
class NavigationTreeTest extends PmaTestCase
{
    /**
     * @var NavigationTree
     */
    protected $object;

    /**
     * Sets up the fixture.
     *
     * @access protected
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['user'] = 'user';
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfgRelation']['db'] = 'pmadb';
        $GLOBALS['cfgRelation']['navigationhiding'] = 'navigationhiding';
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = true;
        $GLOBALS['cfg']['ShowDatabasesNavigationAsTree']  = true;

        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = '';

        $this->object = new NavigationTree(new Template(), $GLOBALS['dbi']);
    }

    /**
     * Tears down the fixture.
     *
     * @access protected
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->object);
    }

    /**
     * Very basic rendering test.
     *
     * @return void
     */
    public function testRenderState()
    {
        $result = $this->object->renderState();
        $this->assertStringContainsString('pma_quick_warp', $result);
    }

    /**
     * Very basic path rendering test.
     *
     * @return void
     */
    public function testRenderPath()
    {
        $result = $this->object->renderPath();
        $this->assertStringContainsString('list_container', $result);
    }

    /**
     * Very basic select rendering test.
     *
     * @return void
     */
    public function testRenderDbSelect()
    {
        $result = $this->object->renderDbSelect();
        $this->assertStringContainsString('pma_navigation_select_database', $result);
    }
}
