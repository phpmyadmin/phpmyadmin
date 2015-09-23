<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_NavigationTree class
 *
 * @package PhpMyAdmin-test
 */

/*
 * we must set $GLOBALS['server'] here
 * since 'check_user_privileges.lib.php' will use it globally
 */
$GLOBALS['server'] = 0;
$GLOBALS['cfg']['Server']['DisableIS'] = false;

require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/navigation/NavigationTree.class.php';
require_once 'libraries/navigation/NodeFactory.class.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/RecentFavoriteTable.class.php';
require_once 'libraries/check_user_privileges.lib.php';

/**
 * Tests for PMA_NavigationTree class
 *
 * @package PhpMyAdmin-test
 */
class PMA_NavigationTreeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PMA_NavigationTree
     */
    protected $object;

    /**
     * Sets up the fixture.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['PMA_Config'] = new PMA_Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['user'] = 'root';
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = true;
        $GLOBALS['cfg']['ShowDatabasesNavigationAsTree']  = true;

        $GLOBALS['pmaThemeImage'] = 'image';
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $this->object = new PMA_NavigationTree();
    }

    /**
     * Tears down the fixture.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
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
        $this->assertContains('pma_quick_warp', $result);
    }

    /**
     * Very basic path rendering test.
     *
     * @return void
     */
    public function testRenderPath()
    {
        $result = $this->object->renderPath();
        $this->assertContains('list_container', $result);
    }

    /**
     * Very basic select rendering test.
     *
     * @return void
     */
    public function testRenderDbSelect()
    {
        $result = $this->object->renderDbSelect();
        $this->assertContains('pma_navigation_select_database', $result);
    }
}
