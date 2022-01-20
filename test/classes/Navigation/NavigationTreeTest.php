<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation;

use PhpMyAdmin\Navigation\NavigationTree;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Url;
use ReflectionMethod;
use function str_repeat;
use function parse_url;
use function parse_str;
use function htmlspecialchars_decode;

class NavigationTreeTest extends AbstractTestCase
{
    /** @var NavigationTree */
    protected $object;

    /**
     * Sets up the fixture.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        parent::setGlobalConfig();
        parent::setTheme();
        $GLOBALS['server'] = 1;
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['user'] = 'user';
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfgRelation']['db'] = 'pmadb';
        $GLOBALS['cfgRelation']['navigationhiding'] = 'navigationhiding';
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = true;
        $GLOBALS['cfg']['ShowDatabasesNavigationAsTree']  = true;

        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = '';
        $GLOBALS['PMA_PHP_SELF'] = '';

        $this->object = new NavigationTree(new Template(), $GLOBALS['dbi']);
    }

    /**
     * Tears down the fixture.
     *
     * @access protected
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Very basic rendering test.
     */
    public function testRenderState(): void
    {
        $result = $this->object->renderState();
        $this->assertStringContainsString('pma_quick_warp', $result);
    }

    /**
     * Very basic path rendering test.
     */
    public function testRenderPath(): void
    {
        $result = $this->object->renderPath();
        $this->assertIsString($result);
        $this->assertStringContainsString('list_container', $result);
    }

    /**
     * Very basic select rendering test.
     */
    public function testRenderDbSelect(): void
    {
        $result = $this->object->renderDbSelect();
        $this->assertStringContainsString('pma_navigation_select_database', $result);
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
