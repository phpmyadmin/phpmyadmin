<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA_Util::showDocu from Util.class.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/php-gettext/gettext.inc';

class PMA_showDocu_test extends PHPUnit_Framework_TestCase
{
    function setup()
    {
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION[' PMA_token '] = 'token';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['server'] = '99';
        $GLOBALS['cfg']['ServerDefault'] = 1;
    }

    function testShowDocu()
    {
        $this->assertEquals(
            '<a href="./url.php?url=http%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Fpage.html%23anchor&amp;server=99&amp;lang=en&amp;token=token" target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help" /></a>',
            PMA_Util::showDocu('page', 'anchor')
        );

    }
}
