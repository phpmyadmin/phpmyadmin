<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_Theme_Manager class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Theme_Manager.class.php';

class PMA_Theme_Manager_test extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $GLOBALS['cfg']['ThemePath'] = './themes';
        $GLOBALS['cfg']['ThemePerServer'] = false;
        $GLOBALS['cfg']['ThemeDefault'] = 'pmahomme';
        $GLOBALS['cfg']['ServerDefault'] = 0;
        $GLOBALS['server'] = 99;
        $_SESSION[' PMA_token '] = 'token';
    }

    public function testCookieName()
    {
        $tm = new PMA_Theme_Manager();
        $this->assertEquals('pma_theme', $tm->getThemeCookieName());
    }

    public function testPerServerCookieName()
    {
        $tm = new PMA_Theme_Manager();
        $tm->setThemePerServer(true);
        $this->assertEquals('pma_theme-99', $tm->getThemeCookieName());
    }

    public function testHtmlSelectBox()
    {
        $tm = new PMA_Theme_Manager();
        $this->assertContains('<option value="pmahomme" selected="selected">', $tm->getHtmlSelectBox());
    }

}
?>

