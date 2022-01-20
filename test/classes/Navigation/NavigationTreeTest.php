<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PhpMyAdmin\Navigation\NavigationTree class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Navigation;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\NavigationTree;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Theme;
use PhpMyAdmin\Url;
use ReflectionMethod;

/*
 * we must set $GLOBALS['server'] here
 * since 'check_user_privileges.inc.php' will use it globally
 */
$GLOBALS['server'] = 0;
$GLOBALS['cfg']['Server']['DisableIS'] = false;

require_once 'libraries/check_user_privileges.inc.php';

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
    protected function setUp()
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->set('URLQueryEncryption', false);
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['user'] = 'root';
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = true;
        $GLOBALS['cfg']['ShowDatabasesNavigationAsTree']  = true;

        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['db'] = 'db';

        $this->object = new NavigationTree();
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

    /**
     * @return void
     */
    public function testEncryptQueryParams()
    {
        global $PMA_Config;

        $_SESSION = [];
        $PMA_Config->set('URLQueryEncryption', false);
        $PMA_Config->set('URLQueryEncryptionSecretKey', str_repeat('a', 32));

        $method = new ReflectionMethod($this->object, 'encryptQueryParams');
        $method->setAccessible(true);

        $link = 'tbl_structure.php?server=1&amp;db=test_db&amp;table=test_table&amp;pos=0';

        $actual = $method->invoke($this->object, $link);
        $this->assertEquals($link, $actual);

        $PMA_Config->set('URLQueryEncryption', true);

        $actual = $method->invoke($this->object, $link);
        $this->assertStringStartsWith('tbl_structure.php?server=1&amp;pos=0&amp;eq=', $actual);

        $url = parse_url($actual);
        parse_str(htmlspecialchars_decode($url['query']), $query);

        $this->assertRegExp('/^[a-zA-Z0-9-_=]+$/', $query['eq']);
        $decrypted = Url::decryptQuery($query['eq']);
        $this->assertJson($decrypted);
        $this->assertSame('{"db":"test_db","table":"test_table"}', $decrypted);
    }
}
